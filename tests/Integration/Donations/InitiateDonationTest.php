<?php

namespace ErgoSarapu\DonationBundle\Tests\Integration\Donations;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetPendingDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\ValueObject\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationStatus;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsCaptured;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPendingPayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\ValueObject\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\Tests\Integration\IntegrationTestingKernel;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngine;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\Tests\Helpers\DonationBundleTestingKernel;

class InitiateDonationTest extends KernelTestCase
{

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
        $initiateDonation = new InitiateDonation($amount, CampaignId::generate(), new Gateway('test'));
        $this->commandBus->dispatch($initiateDonation);

        /** @var ?Donation $donation */
        $donation = $this->queryBus->ask(new GetPendingDonation($initiateDonation->donationId));
        $this->assertNotNull($donation);
        $this->assertEquals(DonationStatus::Pending->value, $donation->getStatus());

        /** @var ?Payment $payment */
        $payment = $this->queryBus->ask(new GetPendingPayment(PaymentId::fromString($donation->getPaymentId())));
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->getRedirectUrl());
        $this->assertEquals(PaymentStatus::Pending->value, $payment->getStatus());

        // Mark payment as captured and expect donation to be accepted
        $this->commandBus->dispatch(new MarkPaymentAsCaptured(PaymentId::fromString($donation->getPaymentId()), $amount));
        $payment = $this->queryBus->ask(new GetPayment(PaymentId::fromString($donation->getPaymentId())));
        $this->assertEquals(PaymentStatus::Captured->value, $payment->getStatus());
        $donation = $this->queryBus->ask(new GetDonation($initiateDonation->donationId));
        $this->assertEquals(DonationStatus::Accepted->value, $donation->getStatus());
    }
}
