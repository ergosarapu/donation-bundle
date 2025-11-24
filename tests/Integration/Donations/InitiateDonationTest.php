<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Integration\Donations;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateRecurringDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetPendingDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetPendingRecurringDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetRecurringDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\RecurringDonation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\ValueObject\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationStatus;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationStatus;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringInterval;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsCaptured;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsFailed;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPendingPayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\ValueObject\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\Tests\Helpers\DonationBundleTestingKernel;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngine;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

class InitiateDonationTest extends KernelTestCase
{
    use InteractsWithMessenger;

    private CommandBusInterface $commandBus;

    private QueryBusInterface $queryBus;

    private SubscriptionEngine $subscriptionEngine;

    public function setUp(): void
    {
        parent::setUp();
        $this->commandBus = static::getContainer()->get(CommandBusInterface::class);
        $this->queryBus = static::getContainer()->get(QueryBusInterface::class);
        $this->subscriptionEngine = static::getContainer()->get(SubscriptionEngine::class);
        $this->subscriptionEngine->setup();
        $this->subscriptionEngine->boot();
    }

    public function tearDown(): void
    {
        $this->subscriptionEngine->remove();
        parent::tearDown();
    }

    protected static function getKernelClass(): string
    {
        return DonationBundleTestingKernel::class;
    }

    public function testInitiateAndAcceptDonation(): void
    {
        // Initiate donation
        $amount = new Money(100, new Currency('EUR'));
        $initiateDonation = new InitiateDonation(DonationId::generate(), $amount, CampaignId::generate(), new Gateway('test'));
        $this->commandBus->dispatch($initiateDonation);

        /** @var ?Donation $donation */
        $donation = $this->queryBus->ask(new GetPendingDonation($initiateDonation->donationId));
        $this->assertNotNull($donation);
        $this->assertEquals(DonationStatus::Pending, $donation->getStatus());

        /** @var ?Payment $payment */
        $payment = $this->queryBus->ask(new GetPendingPayment(PaymentId::fromString($donation->getPaymentId())));
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->getRedirectUrl());
        $this->assertEquals(PaymentStatus::Pending, $payment->getStatus());

        // Mark payment as captured and expect donation to be accepted
        $this->commandBus->dispatch(new MarkPaymentAsCaptured(PaymentId::fromString($donation->getPaymentId()), $amount));

        /** @var ?Payment $payment */
        $payment = $this->queryBus->ask(new GetPayment(PaymentId::fromString($donation->getPaymentId())));
        $this->assertNotNull($payment);
        $this->assertEquals(PaymentStatus::Captured, $payment->getStatus());

        /** @var ?Donation $donation */
        $donation = $this->queryBus->ask(new GetDonation($initiateDonation->donationId));
        $this->assertNotNull($donation);
        $this->assertEquals(DonationStatus::Accepted, $donation->getStatus());
    }

    public function testInitiateAndFailDonation(): void
    {
        // Initiate donation
        $amount = new Money(100, new Currency('EUR'));
        $initiateDonation = new InitiateDonation(DonationId::generate(), $amount, CampaignId::generate(), new Gateway('test'));
        $this->commandBus->dispatch($initiateDonation);

        /** @var ?Donation $donation */
        $donation = $this->queryBus->ask(new GetPendingDonation($initiateDonation->donationId));
        $this->assertNotNull($donation);
        $this->assertEquals(DonationStatus::Pending, $donation->getStatus());

        // Mark payment as not succeeded and expect donation to be failed
        $this->commandBus->dispatch(new MarkPaymentAsFailed(PaymentId::fromString($donation->getPaymentId())));

        /** @var ?Payment $payment */
        $payment = $this->queryBus->ask(new GetPayment(PaymentId::fromString($donation->getPaymentId())));
        $this->assertNotNull($payment);
        $this->assertEquals(PaymentStatus::Failed, $payment->getStatus());

        /** @var ?Donation $donation */
        $donation = $this->queryBus->ask(new GetDonation($initiateDonation->donationId));
        $this->assertNotNull($donation);
        $this->assertEquals(DonationStatus::Failed, $donation->getStatus());
    }

    public function testInitiateAndActivateRecurringDonation(): void
    {
        // Initiate recurring donation
        $amount = new Money(100, new Currency('EUR'));
        $initiateRecurringDonation = new InitiateRecurringDonation(
            $amount,
            CampaignId::generate(),
            new Gateway('test'),
            new RecurringInterval(RecurringInterval::Monthly),
            new Email('example@example.com')
        );
        $this->commandBus->dispatch($initiateRecurringDonation);

        /** @var ?RecurringDonation $recurringDonation */
        $recurringDonation = $this->queryBus->ask(new GetPendingRecurringDonation($initiateRecurringDonation->recurringDonationId));
        $this->assertNotNull($recurringDonation);
        $this->assertEquals(RecurringDonationStatus::Pending, $recurringDonation->getStatus());

        /** @var ?Donation $donation */
        $donation = $this->queryBus->ask(new GetPendingDonation(DonationId::fromString($recurringDonation->getActivationDonationId())));
        $this->assertNotNull($donation);
        $this->assertEquals(DonationStatus::Pending, $donation->getStatus());

        // Mark payment as captured and expect donation to be accepted
        $this->commandBus->dispatch(new MarkPaymentAsCaptured(PaymentId::fromString($donation->getPaymentId()), $amount));

        /** @var ?Payment $payment */
        $payment = $this->queryBus->ask(new GetPayment(PaymentId::fromString($donation->getPaymentId())));
        $this->assertNotNull($payment);
        $this->assertEquals(PaymentStatus::Captured, $payment->getStatus());

        /** @var ?Donation $donation */
        $donation = $this->queryBus->ask(new GetDonation(DonationId::fromString($donation->getId())));
        $this->assertNotNull($donation);
        $this->assertEquals(DonationStatus::Accepted, $donation->getStatus());

        /** @var ?RecurringDonation $recurringDonation */
        $recurringDonation = $this->queryBus->ask(new GetRecurringDonation($initiateRecurringDonation->recurringDonationId));
        $this->assertNotNull($recurringDonation);
        $this->assertEquals(RecurringDonationStatus::Active, $recurringDonation->getStatus());
    }

}
