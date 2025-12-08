<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Acceptance;

use DateInterval;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateRecurringPlanRenewal;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\DonationAccepted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\DonationFailed;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\DonationInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanActivated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanFailed;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanFailing;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanRenewalCompleted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanRenewalInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\RecurringPlanId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\RecurringInterval;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command\InitiatePaymentIntegrationCommand;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentDidNotSucceedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentSucceededIntegrationEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Patchlevel\EventSourcing\Clock\FrozenClock;
use Psr\Clock\ClockInterface;

class DonationsTest extends AcceptanceTestCase
{
    private CampaignId $campaignId;

    private RecurringPlanId $recurringPlanId;

    private RecurringInterval $recurringInterval;

    private DonationId $donationId;

    private PaymentId $paymentId;

    private FrozenClock $clock;

    public function setUp(): void
    {
        parent::setUp();
        $clock = static::getContainer()->get(ClockInterface::class);
        $this->assertInstanceOf(FrozenClock::class, $clock);
        $this->clock = $clock;
    }

    public function tearDown(): void
    {
        unset($this->campaignId);
        unset($this->recurringPlanId);
        unset($this->recurringInterval);
        unset($this->paymentId);
        unset($this->donationId);
        parent::tearDown();
    }

    public function testDonationInitiated(): void
    {
        $this->givenDonationInitiated(new Money(100, new Currency('EUR')));
    }

    public function testRecurringPlanInitiated(): void
    {
        $this->givenRecurringPlanInitiated(new Money(100, new Currency('EUR')), new RecurringInterval(RecurringInterval::Monthly));
    }

    public function testPaymentSucceededAcceptsDonation(): void
    {
        $this->givenDonationInitiated(new Money(100, new Currency('EUR')));
        $this->whenPaymentSucceeded(new Money(100, new Currency('EUR')));
        $this->thenDonationAccepted();
        $this->thenRecurringPlanNotActivated();
    }

    public function testPaymentDidNotSucceedFailsDonation(): void
    {
        $this->givenDonationInitiated(new Money(100, new Currency('EUR')));
        $this->whenPaymentDidNotSucceed();
        $this->thenDonationFailed();
    }

    public function testPaymentSucceededActivatesRecurringPlan(): void
    {
        $this->givenRecurringPlanActivated(new Money(100, new Currency('EUR')), new RecurringInterval(RecurringInterval::Monthly));
    }

    public function testPaymentDidNotSucceedFailsRecurringPlan(): void
    {
        $this->givenRecurringPlanInitiated(new Money(100, new Currency('EUR')), new RecurringInterval(RecurringInterval::Monthly));
        $this->whenPaymentDidNotSucceed();
        $this->thenDonationFailed();
        $this->thenRecurringPlanFailed();
    }

    public function testRecurringPlanRenewed(): void
    {
        $this->givenRecurringPlanActivated(new Money(100, new Currency('EUR')), new RecurringInterval(RecurringInterval::Monthly));
        $this->whenTimeAdvancesBy($this->recurringInterval->toDateInterval());
        $this->thenRecurringPlanRenewalInitiated();
        $this->thenDonationInitiated(new Money(100, new Currency('EUR')));
        $this->thenInitiatePaymentDispatched(new Money(100, new Currency('EUR')));
        $this->whenPaymentSucceeded(new Money(100, new Currency('EUR')));
        $this->thenDonationAccepted();
        $this->thenRecurringPlanRenewalCompleted();
    }

    public function testRecurringPlanFailing(): void
    {
        $this->givenRecurringPlanActivated(new Money(100, new Currency('EUR')), new RecurringInterval(RecurringInterval::Monthly));
        $this->whenTimeAdvancesBy($this->recurringInterval->toDateInterval());
        $this->thenRecurringPlanRenewalInitiated();
        $this->thenDonationInitiated(new Money(100, new Currency('EUR')));
        $this->thenInitiatePaymentDispatched(new Money(100, new Currency('EUR')));
        $this->whenPaymentDidNotSucceed();
        $this->thenDonationFailed();
        $this->thenRecurringPlanFailing();
    }

    private function clearTransports(): void
    {
        $this->transport('command')->reset();
        $this->transport('event')->reset();
        $this->transport('integration_command')->reset();
        $this->transport('integration_event')->reset();
    }
    private function givenCampaignExists(): void
    {
        $this->campaignId = CampaignId::generate();
    }

    private function givenDonationInitiated(Money $amount): void
    {
        $this->givenCampaignExists();
        $this->whenInitiateDonation($amount);
        $this->thenDonationInitiated($amount);
        $this->thenInitiatePaymentDispatched($amount);
    }

    private function givenRecurringPlanInitiated(Money $amount, RecurringInterval $interval): void
    {
        $this->givenCampaignExists();
        $this->whenInitiateRecurringPlan($amount, $interval);
        $this->thenRecurringPlanInitiated($amount);
        $this->thenDonationInitiated($amount);
        $this->thenInitiatePaymentDispatched($amount);
    }

    private function givenRecurringPlanActivated(Money $amount, RecurringInterval $interval): void
    {
        $this->givenRecurringPlanInitiated($amount, $interval);
        $this->whenPaymentSucceeded($amount);
        $this->thenDonationAccepted();
        $this->thenRecurringPlanActivated();
        $this->thenRecurringPlanRenewalDelayed();
    }

    private function whenInitiateDonation(Money $amount): void
    {
        $this->clearTransports();
        $initiateDonation = new InitiateDonation(
            DonationId::generate(),
            $amount,
            $this->campaignId,
            new Gateway('test')
        );
        $this->transport('command')->send($initiateDonation);
    }

    private function whenInitiateRecurringPlan(Money $amount, RecurringInterval $interval): void
    {
        $this->clearTransports();
        $initiateRecurringPlan = new InitiateRecurringPlan(
            $amount,
            $this->campaignId,
            new Gateway('test'),
            $interval,
            new Email('example@example.com')
        );
        $this->commandBus->dispatch($initiateRecurringPlan);
        $this->transport('command')->dispatched()->assertContains(InitiateRecurringPlan::class, 1);
    }

    private function whenTimeAdvancesBy(DateInterval $dateInterval): void
    {
        $this->clock->update($this->clock->now()->add($dateInterval));
        $this->transport('delayed_command')->processOrFail();
    }
    private function whenPaymentSucceeded(Money $amount): void
    {
        $this->clearTransports();
        $this->transport('integration_event')->send(
            new PaymentSucceededIntegrationEvent(
                $this->paymentId,
                $amount,
                PaymentAppliedToId::fromString($this->donationId->toString())
            )
        );
        // $this->transport('command')->intercept(); // Expect delayed command to be intercepted
        $this->transport('integration_event')->processOrFail();
    }

    private function whenPaymentDidNotSucceed(): void
    {
        $this->clearTransports();
        $this->transport('integration_event')->send(
            new PaymentDidNotSucceedIntegrationEvent(
                $this->paymentId,
                PaymentAppliedToId::fromString($this->donationId->toString())
            )
        );
        $this->transport('integration_event')->processOrFail();
    }
    private function thenDonationInitiated(Money $amount): void
    {
        $event = $this->transport('event')->dispatched()->assertContains(DonationInitiated::class, 1)->messages(DonationInitiated::class)[0];
        $this->assertEquals($amount, $event->amount);
        $this->donationId = $event->donationId;
    }
    private function thenRecurringPlanInitiated(Money $amount): void
    {
        $event = $this->bus('event.bus')->dispatched()->assertContains(RecurringPlanInitiated::class, 1)->messages(RecurringPlanInitiated::class)[0];
        $this->assertEquals($amount, $event->amount);
        $this->recurringPlanId = $event->id;
        $this->recurringInterval = $event->interval;
    }






    private function thenDonationAccepted(): void
    {
        $this->transport('event')->dispatched()->assertContains(DonationAccepted::class, 1);
    }

    private function thenDonationFailed(): void
    {
        $this->transport('event')->dispatched()->assertContains(DonationFailed::class, 1);
    }

    private function thenRecurringPlanActivated(): void
    {
        $this->transport('event')->dispatched()->assertContains(RecurringPlanActivated::class, 1);
    }

    private function thenRecurringPlanRenewalDelayed(): void
    {
        $this->transport('delayed_command')->dispatched()->assertContains(InitiateRecurringPlanRenewal::class, 1);
        $this->transport('delayed_command')->acknowledged()->assertCount(0);
        $this->transport('delayed_command')->queue()->assertContains(InitiateRecurringPlanRenewal::class, 1);
    }

    private function thenRecurringPlanFailed(): void
    {
        $this->transport('event')->dispatched()->assertContains(RecurringPlanFailed::class, 1);
    }

    private function thenRecurringPlanFailing(): void
    {
        $this->transport('event')->dispatched()->assertContains(RecurringPlanFailing::class, 1);
    }

    private function thenRecurringPlanNotActivated(): void
    {
        $this->transport('event')->dispatched()->assertNotContains(RecurringPlanActivated::class);
    }



    private function thenRecurringPlanRenewalInitiated(): void
    {
        $event = $this->transport('event')->dispatched()->assertContains(RecurringPlanRenewalInitiated::class, 1)->messages(RecurringPlanRenewalInitiated::class)[0];
    }

    private function thenRecurringPlanRenewalCompleted(): void
    {
        $this->transport('event')->dispatched()->assertContains(RecurringPlanRenewalCompleted::class, 1);
    }

    private function thenInitiatePaymentDispatched(Money $amount): void
    {
        $command = $this->transport('integration_command')->dispatched()->assertContains(InitiatePaymentIntegrationCommand::class, 1)->messages(InitiatePaymentIntegrationCommand::class)[0];
        $this->assertEquals($amount, $command->amount);
        $this->paymentId = $command->paymentId;
    }
}
