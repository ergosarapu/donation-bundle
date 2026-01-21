<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Acceptance\Donations\Behat;

use Behat\Behat\Context\Context;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ActivateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateRecurringPlanRenewal;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationAccepted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationFailed;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationRequest;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonorIdentity;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringInterval;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanActionIntent;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanActivated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanFailed;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanFailing;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanRenewalCompleted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanRenewalInitiated;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodActionIntent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command\InitiatePaymentIntegrationCommand;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentDidNotSucceedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentMethodUnusableIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentSucceededIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\UnusablePaymentMethodCreatedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\UsablePaymentMethodCreatedIntegrationEvent;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Patchlevel\EventSourcing\Clock\FrozenClock;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngine;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Webmozart\Assert\Assert;
use Zenstruck\Messenger\Test\Transport\TestTransport;

class DonationsContext implements Context
{
    private DonationId $lastDonationId;
    private PaymentId $lastPaymentId;
    private RecurringPlanId $lastRecurringPlanId;
    private PaymentMethodId $lastPaymentMethodId;
    private RecurringInterval $lastRecurringInterval;
    private FrozenClock $clock;

    public function __construct(
        #[Autowire(service: 'messenger.transport.command')]
        private readonly TestTransport $commandTransport,
        #[Autowire(service: 'messenger.transport.integration_command')]
        private readonly TestTransport $integrationCommandTransport,
        #[Autowire(service: 'messenger.transport.event')]
        private readonly TestTransport $eventTransport,
        #[Autowire(service: 'messenger.transport.integration_event')]
        private readonly TestTransport $integrationEventTransport,
        #[Autowire(service: 'messenger.transport.delayed')]
        private readonly TestTransport $delayedTransport,
        private readonly SubscriptionEngine $subscriptionEngine,
        private readonly CommandBusInterface $commandBus,
        private readonly EventBusInterface $eventBus,
        ClockInterface $clock,
    ) {
        Assert::isInstanceOf($clock, FrozenClock::class);
        $this->clock = $clock;
        $this->subscriptionEngine->setup();
        $this->subscriptionEngine->boot();
    }

    #[BeforeScenario]
    public function resetTransports(): void
    {
        $this->eventTransport->reset();
        $this->integrationEventTransport->reset();
        $this->commandTransport->reset();
        $this->integrationCommandTransport->reset();
        $this->delayedTransport->reset();
    }

    private function getDefaultTestMoney(): Money
    {
        return new Money(1000, new Currency('EUR'));
    }

    #[When('initiate one time donation')]
    public function initiateOneTimeDonation(): void
    {
        $this->lastDonationId = DonationId::generate();
        $donationRequest = new DonationRequest(
            $this->lastDonationId,
            CampaignId::generate(),
            $this->getDefaultTestMoney(),
            new Gateway('test'),
            new DonorIdentity(),
        );
        $initiateDonation = new InitiateDonation($donationRequest);
        $this->commandBus->dispatch($initiateDonation);

    }

    #[When('initiate recurring plan')]
    public function initiateRecurringPlan(): void
    {
        $this->lastDonationId = DonationId::generate();
        $donationRequest = new DonationRequest(
            $this->lastDonationId,
            CampaignId::generate(),
            $this->getDefaultTestMoney(),
            new Gateway('test'),
            new DonorIdentity(
                new Email('example@example.com')
            ),
        );
        $this->lastRecurringInterval = new RecurringInterval(RecurringInterval::Monthly);
        $initiateRecurringPlan = new InitiateRecurringPlan($this->lastRecurringInterval, $donationRequest);
        $this->lastRecurringPlanId = $initiateRecurringPlan->recurringPlanId;
        $this->commandBus->dispatch($initiateRecurringPlan);
    }


    #[Then('donation is initiated')]
    public function donationIsInitiated(): void
    {
        $this->eventTransport->dispatched()->assertContains(DonationInitiated::class, 1);
        $messages = $this->eventTransport->dispatched()->messages(DonationInitiated::class);

        /** @var DonationInitiated $event */
        $event = $messages[0];
        Assert::eq($event->donationId, $this->lastDonationId, 'Donation ID does not match');
        $this->lastPaymentId = $event->paymentId;
    }

    #[Then('donation is initiated for the renewal')]
    public function donationIsInitiatedForTheRenewal(): void
    {
        $this->eventTransport->dispatched()->assertContains(DonationInitiated::class, 1);
        $messages = $this->eventTransport->dispatched()->messages(DonationInitiated::class);

        /** @var DonationInitiated $event */
        $event = $messages[0];
        $this->lastDonationId = $event->donationId;
        $this->lastPaymentId = $event->paymentId;
        Assert::eq($event->recurringPlanAction?->intent, RecurringPlanActionIntent::Renew, 'Donation is not for renewal');
    }

    #[Then('initiate payment integration command is sent')]
    public function paymentIntegrationCommandIsSent(): void
    {
        $this->integrationCommandTransport->dispatched()->assertContains(InitiatePaymentIntegrationCommand::class, 1);
    }

    #[Then('initiate payment integration command is sent with request to store payment method')]
    public function initiatePaymentIntegrationCommandIsSentWithRequestToStorePaymentMethod(): void
    {
        $this->integrationCommandTransport->dispatched()->assertContains(InitiatePaymentIntegrationCommand::class, 1);
        $messages = $this->integrationCommandTransport->dispatched()->messages(InitiatePaymentIntegrationCommand::class);

        /** @var InitiatePaymentIntegrationCommand $event */
        $event = $messages[0];
        Assert::notNull($event->paymentMethodAction, 'Payment method action should not be null');
        // $this->lastPaymentMethodId = $event->paymentMethodAction->paymentMethodId;
    }

    #[Then('initiate payment integration command is sent with request to use payment method')]
    public function initiatePaymentIntegrationCommandIsSentWithRequestToUsePaymentMethod(): void
    {
        $this->integrationCommandTransport->dispatched()->assertContains(InitiatePaymentIntegrationCommand::class, 1);
        $messages = $this->integrationCommandTransport->dispatched()->messages(InitiatePaymentIntegrationCommand::class);

        /** @var InitiatePaymentIntegrationCommand $event */
        $event = $messages[0];
        Assert::notNull($event->paymentMethodAction, 'Payment method action should not be null');
        Assert::eq($event->paymentMethodAction->intent, PaymentMethodActionIntent::Use, 'Payment method action intent should be Use');
        // $this->lastPaymentMethodId = $event->paymentMethodAction->paymentMethodId;
    }

    #[Then('recurring plan is initiated with the donation as initial donation')]
    public function recurringPlanIsInitiatedWithTheDonationAsInitialDonation(): void
    {
        $this->eventTransport->dispatched()->assertContains(RecurringPlanInitiated::class, 1);
        $messages = $this->eventTransport->dispatched()->messages(RecurringPlanInitiated::class);

        /** @var RecurringPlanInitiated $event */
        $event = $messages[0];
        Assert::eq($event->recurringPlanAction->recurringPlanId, $this->lastRecurringPlanId, 'Recurring Plan ID does not match');
        Assert::eq($event->initialDonationId, $this->lastDonationId, 'Initial Donation ID does not match');
        $this->lastPaymentMethodId = $event->recurringPlanAction->paymentMethodId;
    }

    #[Given('initiated donation exists')]
    public function initiatedDonationExists(): void
    {
        $this->initiateOneTimeDonation();
        $this->donationIsInitiated();
    }

    #[When('payment succeeds')]
    public function paymentSucceeds(): void
    {
        $this->eventBus->dispatch(new PaymentSucceededIntegrationEvent(
            $this->lastPaymentId,
            $this->getDefaultTestMoney(),
            PaymentAppliedToId::fromString($this->lastDonationId->toString()),
        ));
        $this->integrationEventTransport->processOrFail();
    }

    #[When('payment fails')]
    public function paymentFails(): void
    {
        $this->eventBus->dispatch(new PaymentDidNotSucceedIntegrationEvent(
            $this->lastPaymentId,
            PaymentAppliedToId::fromString($this->lastDonationId->toString()),
        ));
        $this->integrationEventTransport->processOrFail();
    }

    #[Then('donation is marked as accepted')]
    public function donationIsAccepted(): void
    {
        $this->eventTransport->dispatched()->assertContains(DonationAccepted::class, 1);
    }

    #[Then('donation is marked as failed')]
    public function donationIsFailed(): void
    {
        $this->eventTransport->dispatched()->assertContains(DonationFailed::class, 1);
    }

    #[Given('initiated recurring plan exists')]
    public function initiatedRecurringPlanExists(): void
    {
        $this->initiateRecurringPlan();
        $this->recurringPlanIsInitiatedWithTheDonationAsInitialDonation();
    }

    #[Given('activated recurring plan exists')]
    public function activatedRecurringPlanExists(): void
    {
        $this->initiatedRecurringPlanExists();
        $this->usablePaymentMethodIsCreated();
        $this->recurringPlanIsMarkedAsActivated();
        $this->recurringPlanIsScheduledForRenewal();
        $this->eventTransport->reset();
        $this->integrationCommandTransport->reset();
    }

    #[When('payment method gets unusable')]
    public function paymentMethodGetsUnusable(): void
    {
        $this->eventBus->dispatch(new PaymentMethodUnusableIntegrationEvent(
            $this->lastPaymentMethodId,
        ));
        $this->integrationEventTransport->processOrFail();
    }

    #[When('usable payment method is created')]
    public function usablePaymentMethodIsCreated(): void
    {
        $this->eventBus->dispatch(new UsablePaymentMethodCreatedIntegrationEvent(
            $this->lastPaymentMethodId,
        ));
        $this->integrationEventTransport->processOrFail();
    }


    #[When('unusable payment method is created')]
    public function unusablePaymentMethodIsCreated(): void
    {
        $this->eventBus->dispatch(new UnusablePaymentMethodCreatedIntegrationEvent(
            $this->lastPaymentMethodId,
        ));
        $this->integrationEventTransport->processOrFail();
    }

    #[Then('recurring plan is marked as activated')]
    public function recurringPlanIsMarkedAsActivated(): void
    {
        $this->eventTransport->dispatched()->assertContains(RecurringPlanActivated::class, 1);
        $messages = $this->eventTransport->dispatched()->messages(RecurringPlanActivated::class);

        /** @var RecurringPlanActivated $event */
        $event = $messages[0];
        Assert::eq($event->id, $this->lastRecurringPlanId, 'Recurring Plan ID does not match');
        // Assert::eq($event->nextRenewalTime) // TODO
    }

    #[Then('recurring plan is scheduled for renewal')]
    public function recurringPlanIsScheduledForRenewal(): void
    {
        $this->delayedTransport->dispatched()->assertContains(InitiateRecurringPlanRenewal::class, 1);
    }

    #[Then('recurring plan is marked as failed')]
    public function recurringPlanIsMarkedAsFailed(): void
    {
        $this->eventTransport->dispatched()->assertContains(RecurringPlanFailed::class, 1);
        $messages = $this->eventTransport->dispatched()->messages(RecurringPlanFailed::class);

        /** @var RecurringPlanFailed $event */
        $event = $messages[0];
        Assert::eq($event->id, $this->lastRecurringPlanId, 'Recurring Plan ID does not match');
    }

    #[When('recurring plan is due for renewal')]
    public function recurringPlanIsDueForRenewal(): void
    {
        $this->clock->update($this->clock->now()->add($this->lastRecurringInterval->toDateInterval()));
        $this->delayedTransport->processOrFail();
        $this->delayedTransport->reset();
    }

    #[Then('recurring plan renewal is initiated')]
    public function recurringPlanRenewalIsInitiated(): void
    {
        $this->eventTransport->dispatched()->assertContains(RecurringPlanRenewalInitiated::class, 1);
    }

    #[Then('recurring plan renewal is completed')]
    public function recurringPlanRenewalIsCompleted(): void
    {
        $this->eventTransport->dispatched()->assertContains(RecurringPlanRenewalCompleted::class, 1);
        $messages = $this->eventTransport->dispatched()->messages(RecurringPlanRenewalCompleted::class);
        /** @var RecurringPlanRenewalCompleted $event */
        $event = $messages[0];
        Assert::eq($event->nextRenewalTime, $this->clock->now()->add($this->lastRecurringInterval->toDateInterval()), 'Next renewal time does not match expected value');
    }

    #[Then('recurring plan is marked as failing')]
    public function recurringPlanIsMarkedAsFailing(): void
    {
        $this->eventTransport->dispatched()->assertContains(RecurringPlanFailing::class, 1);
    }

    #[When('recurring plan is activated')]
    public function recurringPlanIsActivated(): void
    {
        $this->commandBus->dispatch(new ActivateRecurringPlan(
            $this->lastRecurringPlanId,
        ));
    }
}
