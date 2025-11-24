<?php

declare(strict_types=1);

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
use ErgoSarapu\DonationBundle\Tests\Acceptance\AcceptanceTestCase;

class DonationsTest extends AcceptanceTestCase
{
    private CampaignId $campaignId;

    private RecurringDonationId $recurringDonationId;

    private DonationId $donationId;

    private PaymentId $paymentId;

    public function tearDown(): void
    {
        unset($this->campaignId);
        unset($this->recurringDonationId);
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
        $this->givenRecurringDonationInitiated(new Money(100, new Currency('EUR')));
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
        $this->givenActivatedRecurringDonation();
    }

    public function testPaymentDidNotSucceedFailsRecurringDonation(): void
    {
        $this->givenRecurringDonationInitiated(new Money(100, new Currency('EUR')));
        $this->whenPaymentDidNotSucceed();
        $this->thenDonationFailed();
        $this->thenRecurringDonationFailed();
    }

    public function testRecurringDonationRenewed(): void
    {
        $this->givenActivatedRecurringDonation();
        $this->whenInitiateRecurringDonationRenewal();
        $this->thenRecurringDonationRenewalInitiated();
        $this->thenDonationInitiated(new Money(100, new Currency('EUR')));
        $this->thenInitiatePaymentDispatched(new Money(100, new Currency('EUR')));
        $this->whenPaymentSucceeded(new Money(100, new Currency('EUR')));
        $this->thenDonationAccepted();
        $this->thenRecurringDonationRenewalCompleted();
    }

    public function testRecurringDonationFailing(): void
    {
        $this->givenActivatedRecurringDonation();
        $this->whenInitiateRecurringDonationRenewal();
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

    private function givenRecurringDonationInitiated(Money $amount): void
    {
        $this->givenCampaignExists();
        $this->whenInitiateRecurringDonation($amount);
        $this->thenRecurringDonationInitiated($amount);
        $this->thenDonationInitiated($amount);
        $this->thenInitiatePaymentDispatched($amount);
    }

    private function givenActivatedRecurringDonation(): void
    {
        $this->givenRecurringDonationInitiated(new Money(100, new Currency('EUR')));
        $this->whenPaymentSucceeded(new Money(100, new Currency('EUR')));
        $this->thenDonationAccepted();
        $this->thenRecurringDonationActivated();
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

    private function whenInitiateRecurringDonation(Money $amount): void
    {
        $this->clearTransports();
        $initiateRecurringDonation = new InitiateRecurringDonation(
            $amount,
            $this->campaignId,
            new Gateway('test'),
            new RecurringInterval(RecurringInterval::Monthly),
            new Email('example@example.com')
        );
        $this->commandBus->dispatch($initiateRecurringDonation);
        $this->transport('command')->dispatched()->assertContains(InitiateRecurringDonation::class, 1);
    }

    private function whenInitiateRecurringDonationRenewal(): void
    {
        $this->clearTransports();
        $this->commandBus->dispatch(new InitiateRecurringDonationRenewal($this->recurringDonationId));
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
        $this->transport('event')->dispatched()->assertNotContains(RecurringDonationActivated::class, 1);
    }



    private function thenRecurringDonationRenewalInitiated(): void
    {
        $this->transport('event')->dispatched()->assertContains(RecurringDonationRenewalInitiated::class, 1);
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
