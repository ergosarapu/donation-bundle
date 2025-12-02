<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Acceptance;

use DateInterval;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateRecurringDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateRecurringDonationRenewal;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\ValueObject\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\DonationAccepted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\DonationFailed;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\DonationInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationActivated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationFailed;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationFailing;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationRenewalCompleted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationRenewalInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringInterval;
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

    private RecurringDonationId $recurringDonationId;

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
        unset($this->recurringDonationId);
        unset($this->recurringInterval);
        unset($this->paymentId);
        unset($this->donationId);
        parent::tearDown();
    }

    public function testDonationInitiated(): void
    {
        $this->givenDonationInitiated(new Money(100, new Currency('EUR')));
    }

    public function testRecurringDonationInitiated(): void
    {
        $this->givenRecurringDonationInitiated(new Money(100, new Currency('EUR')), new RecurringInterval(RecurringInterval::Monthly));
    }

    public function testPaymentSucceededAcceptsDonation(): void
    {
        $this->givenDonationInitiated(new Money(100, new Currency('EUR')));
        $this->whenPaymentSucceeded(new Money(100, new Currency('EUR')));
        $this->thenDonationAccepted();
        $this->thenRecurringDonationNotActivated();
    }

    public function testPaymentDidNotSucceedFailsDonation(): void
    {
        $this->givenDonationInitiated(new Money(100, new Currency('EUR')));
        $this->whenPaymentDidNotSucceed();
        $this->thenDonationFailed();
    }

    public function testPaymentSucceededActivatesRecurringDonation(): void
    {
        $this->givenRecurringDonationActivated(new Money(100, new Currency('EUR')), new RecurringInterval(RecurringInterval::Monthly));
    }

    public function testPaymentDidNotSucceedFailsRecurringDonation(): void
    {
        $this->givenRecurringDonationInitiated(new Money(100, new Currency('EUR')), new RecurringInterval(RecurringInterval::Monthly));
        $this->whenPaymentDidNotSucceed();
        $this->thenDonationFailed();
        $this->thenRecurringDonationFailed();
    }

    public function testRecurringDonationRenewed(): void
    {
        $this->givenRecurringDonationActivated(new Money(100, new Currency('EUR')), new RecurringInterval(RecurringInterval::Monthly));
        $this->whenTimeAdvancesBy($this->recurringInterval->toDateInterval());
        $this->thenRecurringDonationRenewalInitiated();
        $this->thenDonationInitiated(new Money(100, new Currency('EUR')));
        $this->thenInitiatePaymentDispatched(new Money(100, new Currency('EUR')));
        $this->whenPaymentSucceeded(new Money(100, new Currency('EUR')));
        $this->thenDonationAccepted();
        $this->thenRecurringDonationRenewalCompleted();
    }

    public function testRecurringDonationFailing(): void
    {
        $this->givenRecurringDonationActivated(new Money(100, new Currency('EUR')), new RecurringInterval(RecurringInterval::Monthly));
        $this->whenTimeAdvancesBy($this->recurringInterval->toDateInterval());
        $this->thenRecurringDonationRenewalInitiated();
        $this->thenDonationInitiated(new Money(100, new Currency('EUR')));
        $this->thenInitiatePaymentDispatched(new Money(100, new Currency('EUR')));
        $this->whenPaymentDidNotSucceed();
        $this->thenDonationFailed();
        $this->thenRecurringDonationFailing();
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

    private function givenRecurringDonationInitiated(Money $amount, RecurringInterval $interval): void
    {
        $this->givenCampaignExists();
        $this->whenInitiateRecurringDonation($amount, $interval);
        $this->thenRecurringDonationInitiated($amount);
        $this->thenDonationInitiated($amount);
        $this->thenInitiatePaymentDispatched($amount);
    }

    private function givenRecurringDonationActivated(Money $amount, RecurringInterval $interval): void
    {
        $this->givenRecurringDonationInitiated($amount, $interval);
        $this->whenPaymentSucceeded($amount);
        $this->thenDonationAccepted();
        $this->thenRecurringDonationActivated();
        $this->thenRecurringDonationRenewalDelayed();
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

    private function whenInitiateRecurringDonation(Money $amount, RecurringInterval $interval): void
    {
        $this->clearTransports();
        $initiateRecurringDonation = new InitiateRecurringDonation(
            $amount,
            $this->campaignId,
            new Gateway('test'),
            $interval,
            new Email('example@example.com')
        );
        $this->commandBus->dispatch($initiateRecurringDonation);
        $this->transport('command')->dispatched()->assertContains(InitiateRecurringDonation::class, 1);
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
    private function thenRecurringDonationInitiated(Money $amount): void
    {
        $event = $this->bus('event.bus')->dispatched()->assertContains(RecurringDonationInitiated::class, 1)->messages(RecurringDonationInitiated::class)[0];
        $this->assertEquals($amount, $event->amount);
        $this->recurringDonationId = $event->id;
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

    private function thenRecurringDonationActivated(): void
    {
        $this->transport('event')->dispatched()->assertContains(RecurringDonationActivated::class, 1);
    }

    private function thenRecurringDonationRenewalDelayed(): void
    {
        $this->transport('delayed_command')->dispatched()->assertContains(InitiateRecurringDonationRenewal::class, 1);
        $this->transport('delayed_command')->acknowledged()->assertCount(0);
        $this->transport('delayed_command')->queue()->assertContains(InitiateRecurringDonationRenewal::class, 1);
    }

    private function thenRecurringDonationFailed(): void
    {
        $this->transport('event')->dispatched()->assertContains(RecurringDonationFailed::class, 1);
    }

    private function thenRecurringDonationFailing(): void
    {
        $this->transport('event')->dispatched()->assertContains(RecurringDonationFailing::class, 1);
    }

    private function thenRecurringDonationNotActivated(): void
    {
        $this->transport('event')->dispatched()->assertNotContains(RecurringDonationActivated::class);
    }



    private function thenRecurringDonationRenewalInitiated(): void
    {
        $event = $this->transport('event')->dispatched()->assertContains(RecurringDonationRenewalInitiated::class, 1)->messages(RecurringDonationRenewalInitiated::class)[0];
    }

    private function thenRecurringDonationRenewalCompleted(): void
    {
        $this->transport('event')->dispatched()->assertContains(RecurringDonationRenewalCompleted::class, 1);
    }

    private function thenInitiatePaymentDispatched(Money $amount): void
    {
        $command = $this->transport('integration_command')->dispatched()->assertContains(InitiatePaymentIntegrationCommand::class, 1)->messages(InitiatePaymentIntegrationCommand::class)[0];
        $this->assertEquals($amount, $command->amount);
        $this->paymentId = $command->paymentId;
    }
}
