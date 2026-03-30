<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Integration\Donations;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetDonations;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetInitiatedDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetInitiatedRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationRequest;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationStatus;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonorDetails;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringInterval;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanStatus;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsCaptured;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsFailed;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetInitiatedPayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;
use ErgoSarapu\DonationBundle\IntegrationContracts\Donations\Command\InitiateDonationIntegrationCommand;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use ErgoSarapu\DonationBundle\Tests\Helpers\DonationBundleTestingKernel;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngine;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

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
        $donationRequest = new DonationRequest(
            DonationId::generate(),
            CampaignId::generate(),
            $amount,
            new Gateway('test'),
            new DonorDetails(),
            new ShortDescription('Test donation'),
        );
        $initiateDonation = new InitiateDonationIntegrationCommand($donationRequest);
        $this->commandBus->dispatch($initiateDonation);

        /** @var ?Donation $donation */
        $donation = $this->queryBus->ask(new GetInitiatedDonation($donationRequest->donationId));
        $this->assertNotNull($donation);
        $this->assertEquals(DonationStatus::Initiated, $donation->getStatus());

        /** @var ?Payment $payment */
        $payment = $this->queryBus->ask(new GetInitiatedPayment(PaymentId::fromString($donation->getPaymentId())));
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->getRedirectUrl());
        $this->assertEquals(PaymentStatus::Initiated, $payment->getStatus());

        // Mark payment as captured and expect donation to be accepted
        $this->commandBus->dispatch(new MarkPaymentAsCaptured(PaymentId::fromString($donation->getPaymentId()), $amount, null));

        /** @var ?Payment $payment */
        $payment = $this->queryBus->ask(new GetPayment(PaymentId::fromString($donation->getPaymentId())));
        $this->assertNotNull($payment);
        $this->assertEquals(PaymentStatus::Captured, $payment->getStatus());

        /** @var ?Donation $donation */
        $donation = $this->queryBus->ask(new GetDonation($donationRequest->donationId));
        $this->assertNotNull($donation);
        $this->assertEquals(DonationStatus::Accepted, $donation->getStatus());
    }

    public function testInitiateAndFailDonation(): void
    {
        // Initiate donation
        $amount = new Money(100, new Currency('EUR'));
        $donationRequest = new DonationRequest(
            DonationId::generate(),
            CampaignId::generate(),
            $amount,
            new Gateway('test'),
            new DonorDetails(),
            new ShortDescription('Test donation'),
        );
        $initiateDonation = new InitiateDonationIntegrationCommand($donationRequest);
        $this->commandBus->dispatch($initiateDonation);

        /** @var ?Donation $donation */
        $donation = $this->queryBus->ask(new GetInitiatedDonation($donationRequest->donationId));
        $this->assertNotNull($donation);
        $this->assertEquals(DonationStatus::Initiated, $donation->getStatus());

        // Mark payment as not succeeded and expect donation to be failed
        $this->commandBus->dispatch(new MarkPaymentAsFailed(PaymentId::fromString($donation->getPaymentId()), null));

        /** @var ?Payment $payment */
        $payment = $this->queryBus->ask(new GetPayment(PaymentId::fromString($donation->getPaymentId())));
        $this->assertNotNull($payment);
        $this->assertEquals(PaymentStatus::Failed, $payment->getStatus());

        /** @var ?Donation $donation */
        $donation = $this->queryBus->ask(new GetDonation($donationRequest->donationId));
        $this->assertNotNull($donation);
        $this->assertEquals(DonationStatus::Failed, $donation->getStatus());
    }

    public function testRecurringPlanActivateAndRenewal(): void
    {

        // Create a short interval for testing
        // We could mock ClockInterface, but messenger consume and DelayStamp does not support it yet
        // https://github.com/symfony/symfony/issues/62548
        $interval = new RecurringInterval('PT3S'); // 3 seconds

        // Initiate recurring donation
        $amount = new Money(100, new Currency('EUR'));
        $donationRequest = new DonationRequest(
            DonationId::generate(),
            CampaignId::generate(),
            $amount,
            new Gateway('test'),
            new DonorDetails(new Email('example@example.com')),
            new ShortDescription('Test donation'),
        );
        $initiateRecurringPlan = new InitiateRecurringPlan(
            $interval,
            $donationRequest,
        );
        $this->commandBus->dispatch($initiateRecurringPlan);

        // Recurring Plan is initiated
        /** @var ?RecurringPlan $recurringPlan */
        $recurringPlan = $this->queryBus->ask(new GetInitiatedRecurringPlan($initiateRecurringPlan->recurringPlanId));
        $this->assertNotNull($recurringPlan);
        $this->assertEquals(RecurringPlanStatus::Initiated, $recurringPlan->getStatus());
        $this->assertEquals(0, $recurringPlan->getCumulativeReceivedAmount());

        // Initial Donation is initiated
        /** @var ?Donation $activationDonation */
        $activationDonation = $this->queryBus->ask(new GetInitiatedDonation(DonationId::fromString($recurringPlan->getInitialDonationId())));
        $this->assertNotNull($activationDonation);
        $this->assertEquals(DonationStatus::Initiated, $activationDonation->getStatus());

        // Mark payment as captured and expect donation to be accepted
        $this->commandBus->dispatch(new MarkPaymentAsCaptured(PaymentId::fromString($activationDonation->getPaymentId()), $amount, null));

        // Payment is captured
        /** @var ?Payment $payment */
        $payment = $this->queryBus->ask(new GetPayment(PaymentId::fromString($activationDonation->getPaymentId())));
        $this->assertNotNull($payment);
        $this->assertEquals(PaymentStatus::Captured, $payment->getStatus());

        // Activation Donation is accepted
        /** @var ?Donation $activationDonation */
        $activationDonation = $this->queryBus->ask(new GetDonation(DonationId::fromString($activationDonation->getDonationId())));
        $this->assertNotNull($activationDonation);
        $this->assertEquals(DonationStatus::Accepted, $activationDonation->getStatus());

        // Recurring Donation is active
        /** @var ?RecurringPlan $recurringPlan */
        $recurringPlan = $this->queryBus->ask(new GetRecurringPlan($initiateRecurringPlan->recurringPlanId));
        $this->assertNotNull($recurringPlan);
        $this->assertEquals(RecurringPlanStatus::Active, $recurringPlan->getStatus());
        $this->assertEquals($amount->amount(), $recurringPlan->getCumulativeReceivedAmount());

        // Assuming there is initiate renewal command delayed

        // Consume messenger transport 'delayed_command', this should not consume message yet since we do not pass interval yet
        $this->consumeMessagesFromTransport('delayed_command', 1);
        /** @var array<Donation> $donations */
        $donations = $this->queryBus->ask(new GetDonations($initiateRecurringPlan->recurringPlanId));
        $this->assertCount(1, $donations, 'No renewal should be initiated yet. If this happens, it can mean the interval set for testing is too short and may cause the test to be flaky.');

        // Consume messenger transport 'delayed_command', should consume message initiate renewal command
        $this->consumeMessagesFromTransport('delayed_command', 2);
        /** @var array<Donation> $donations */
        $donations = $this->queryBus->ask(new GetDonations($initiateRecurringPlan->recurringPlanId));
        $this->assertCount(2, $donations);
        $donations = array_filter($donations, fn (Donation $d) => $d->getDonationId() != $activationDonation->getDonationId());
        $this->assertCount(1, $donations); // This contains only the renewal donation
        $renewalDonation = array_pop($donations);
        $this->assertNotNull($renewalDonation);

        // Mark payment as captured and expect donation to be accepted
        $this->commandBus->dispatch(new MarkPaymentAsCaptured(PaymentId::fromString($renewalDonation->getPaymentId()), $amount, null));

        // Payment is captured
        /** @var ?Payment $payment */
        $payment = $this->queryBus->ask(new GetPayment(PaymentId::fromString($renewalDonation->getPaymentId())));
        $this->assertNotNull($payment);
        $this->assertEquals(PaymentStatus::Captured, $payment->getStatus());

        // Renewal Donation is accepted
        /** @var ?Donation $renewalDonation */
        $renewalDonation = $this->queryBus->ask(new GetDonation(DonationId::fromString($renewalDonation->getDonationId())));
        $this->assertNotNull($renewalDonation);
        $this->assertEquals(DonationStatus::Accepted, $renewalDonation->getStatus());

        // Recurring Donation is active and has cumulative amount updated
        /** @var ?RecurringPlan $recurringPlan */
        $recurringPlan = $this->queryBus->ask(new GetRecurringPlan($initiateRecurringPlan->recurringPlanId));
        $this->assertNotNull($recurringPlan);
        $this->assertEquals(RecurringPlanStatus::Active, $recurringPlan->getStatus());
        $this->assertEquals(2 * $amount->amount(), $recurringPlan->getCumulativeReceivedAmount());
    }

    private function consumeMessagesFromTransport(string $transportName, int $timeLimit): void
    {
        $application = new \Symfony\Bundle\FrameworkBundle\Console\Application(self::bootKernel());
        $command = $application->find('messenger:consume');

        $input = new ArrayInput([
            'receivers' => [$transportName],
            '--limit' => 1,
            '--time-limit' => $timeLimit,
            '--failure-limit' => 1,
        ]);

        $command->run($input, new ConsoleOutput());
    }
}
