<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Acceptance\Donations\Behat;

use Behat\Behat\Context\Context;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ActivateCampaign;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ArchiveCampaign;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\CreateCampaign;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\UpdateCampaignName;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\UpdateCampaignPublicTitle;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetCampaign;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetPendingDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Campaign;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignName;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignPublicTitle;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignStatus;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationRequest;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationStatus;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonorIdentity;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringInterval;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanStatus;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodActionIntent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Donations\Command\InitiateDonationIntegrationCommand;
use ErgoSarapu\DonationBundle\IntegrationContracts\Donations\Command\ReActivateRecurringPlanIntegrationCommand;
use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationCommandInterface;
use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationEventInterface;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command\InitiatePaymentIntegrationCommand;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentDidNotSucceedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentMethodUnusableIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentSucceededIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\UnusablePaymentMethodCreatedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\UsablePaymentMethodCreatedIntegrationEvent;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use ErgoSarapu\DonationBundle\Tests\Helpers\TestCommandBus;
use ErgoSarapu\DonationBundle\Tests\Helpers\TestEventBus;
use LogicException;
use Patchlevel\EventSourcing\Clock\FrozenClock;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngine;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;
use Webmozart\Assert\Assert;
use Zenstruck\Messenger\Test\Transport\TestTransport;

class DonationsContext implements Context
{
    private DonationId $lastDonationId;
    private PaymentId $lastPaymentId;
    private ?RecurringPlanId $lastRecurringPlanId;
    private PaymentMethodId $lastPaymentMethodId;
    private RecurringInterval $lastRecurringInterval;
    private FrozenClock $clock;
    private CampaignId $lastCampaignId;
    private ?Throwable $lastException = null;

    public function __construct(
        #[Autowire(service: 'messenger.transport.delayed')]
        private readonly TestTransport $delayedTransport,
        private readonly SubscriptionEngine $subscriptionEngine,
        private readonly TestCommandBus $commandBus,
        private readonly TestEventBus $eventBus,
        private readonly QueryBusInterface $queryBus,
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
        $this->eventBus->reset();
        $this->eventBus->intercept(IntegrationEventInterface::class);
        $this->commandBus->reset();
        $this->commandBus->intercept(IntegrationCommandInterface::class);
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
            new ShortDescription('Test donation'),
        );
        $initiateDonation = new InitiateDonationIntegrationCommand($donationRequest);
        $this->commandBus->send($initiateDonation);
    }

    #[When('initiate recurring donation')]
    public function initiateRecurringDonation(): void
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
            new ShortDescription('Test donation'),
        );
        $this->lastRecurringInterval = new RecurringInterval(RecurringInterval::Monthly);
        $initiateRecurringDonation = new InitiateDonationIntegrationCommand($donationRequest, $this->lastRecurringInterval);
        $this->commandBus->send($initiateRecurringDonation);
    }


    #[Then('donation is initiated')]
    public function donationIsInitiated(): void
    {
        $donation = $this->queryBus->ask(new GetPendingDonation($this->lastDonationId));
        Assert::isInstanceOf($donation, Donation::class);
        Assert::notNull($donation, 'No pending donation found');
        $this->lastPaymentId = PaymentId::fromString($donation->getPaymentId());
        $this->lastRecurringPlanId = null;
    }

    #[Then('donation is initiated for the renewal')]
    public function donationIsInitiatedForTheRenewal(): void
    {
        /** @var Donation $donation */
        $donation = $this->queryBus->ask(new GetPendingDonation($this->lastDonationId));
        Assert::notNull($donation, 'No pending donation found');
        $this->lastPaymentId = PaymentId::fromString($donation->getPaymentId());
        Assert::notNull($recurringPlanId = $donation->getRecurringPlanId());
        $this->lastRecurringPlanId = RecurringPlanId::fromString($recurringPlanId);
    }

    #[Then('initiate payment integration command is sent')]
    public function paymentIntegrationCommandIsSent(): void
    {
        $this->commandBus->assertDispatched(InitiatePaymentIntegrationCommand::class, 1);
    }

    #[Then('initiate payment integration command is sent with request to store payment method')]
    public function initiatePaymentIntegrationCommandIsSentWithRequestToStorePaymentMethod(): void
    {
        $this->commandBus->assertDispatched(InitiatePaymentIntegrationCommand::class, 1);
        $messages = $this->commandBus->dispatchedMessages(InitiatePaymentIntegrationCommand::class);

        /** @var InitiatePaymentIntegrationCommand $command */
        $command = $messages[0];
        Assert::notNull($command->paymentRequest->paymentMethodAction);
        Assert::eq($command->paymentRequest->paymentMethodAction->intent, PaymentMethodActionIntent::Request);
        $this->commandBus->resetDispatched();
    }

    #[Then('initiate payment integration command is sent with request to use payment method')]
    public function initiatePaymentIntegrationCommandIsSentWithRequestToUsePaymentMethod(): void
    {
        $this->commandBus->assertDispatched(InitiatePaymentIntegrationCommand::class, 1);
        $messages = $this->commandBus->dispatchedMessages(InitiatePaymentIntegrationCommand::class);

        /** @var InitiatePaymentIntegrationCommand $command */
        $command = $messages[0];
        Assert::notNull($command->paymentRequest->paymentMethodAction);
        Assert::eq($command->paymentRequest->paymentMethodAction->intent, PaymentMethodActionIntent::Use);
        $this->commandBus->resetDispatched();
    }

    #[Then('recurring plan is initiated with the donation as initial donation')]
    public function recurringPlanIsInitiatedWithTheDonationAsInitialDonation(): void
    {
        Assert::notNull($this->lastRecurringPlanId);
        $recurringPlan = $this->queryBus->ask(new GetRecurringPlan($this->lastRecurringPlanId));
        Assert::isInstanceOf($recurringPlan, RecurringPlan::class);
        Assert::isInstanceOf($recurringPlan, RecurringPlan::class);
        Assert::notNull($recurringPlan, 'No recurring plan found');
        Assert::eq($recurringPlan->getInitialDonationId(), $this->lastDonationId->toString());
        Assert::notNull($recurringPlan->getPaymentMethodId());
        $this->lastPaymentMethodId = PaymentMethodId::fromString($recurringPlan->getPaymentMethodId());
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
        $this->eventBus->send(new PaymentSucceededIntegrationEvent(
            $this->lastPaymentId,
            $this->getDefaultTestMoney(),
            PaymentAppliedToId::fromString($this->lastDonationId->toString()),
        ));
    }

    #[When('payment fails')]
    public function paymentFails(): void
    {
        $this->eventBus->send(new PaymentDidNotSucceedIntegrationEvent(
            $this->lastPaymentId,
            PaymentAppliedToId::fromString($this->lastDonationId->toString()),
        ));
    }

    #[Then('donation is marked as accepted')]
    public function donationIsAccepted(): void
    {
        $donation = $this->queryBus->ask(new GetDonation($this->lastDonationId));
        Assert::isInstanceOf($donation, Donation::class);
        Assert::notNull($donation);
        Assert::eq($donation->getStatus(), DonationStatus::Accepted);
    }

    #[Then('donation is marked as failed')]
    public function donationIsFailed(): void
    {
        $donation = $this->queryBus->ask(new GetDonation($this->lastDonationId));
        Assert::isInstanceOf($donation, Donation::class);
        Assert::notNull($donation);
        Assert::eq($donation->getStatus(), DonationStatus::Failed);
    }

    #[Given('initiated recurring plan exists')]
    public function initiatedRecurringPlanExists(): void
    {
        $this->initiateRecurringDonation();
        $this->donationIsInitiatedForTheRenewal();
        $this->recurringPlanIsInitiatedWithTheDonationAsInitialDonation();
    }

    #[Given('activated recurring plan exists')]
    public function activatedRecurringPlanExists(): void
    {
        $this->initiatedRecurringPlanExists();
        $this->initiatePaymentIntegrationCommandIsSentWithRequestToStorePaymentMethod();
        $this->usablePaymentMethodIsCreated();
        $this->recurringPlanIsMarkedAsActivated();
    }

    #[When('payment method gets unusable')]
    public function paymentMethodGetsUnusable(): void
    {
        $this->eventBus->send(new PaymentMethodUnusableIntegrationEvent(
            $this->lastPaymentMethodId,
        ));
    }

    #[When('usable payment method is created')]
    public function usablePaymentMethodIsCreated(): void
    {
        $this->eventBus->send(new UsablePaymentMethodCreatedIntegrationEvent(
            $this->lastPaymentMethodId,
        ));
    }


    #[When('unusable payment method is created')]
    public function unusablePaymentMethodIsCreated(): void
    {
        $this->eventBus->send(new UnusablePaymentMethodCreatedIntegrationEvent(
            $this->lastPaymentMethodId,
        ));
    }

    #[Then('recurring plan is marked as activated')]
    public function recurringPlanIsMarkedAsActivated(): void
    {
        Assert::notNull($this->lastRecurringPlanId);
        $recurringPlan = $this->queryBus->ask(new GetRecurringPlan($this->lastRecurringPlanId));
        Assert::isInstanceOf($recurringPlan, RecurringPlan::class);
        Assert::eq($recurringPlan->getStatus(), RecurringPlanStatus::Active);
        Assert::eq($recurringPlan->getNextRenewalTime(), $this->clock->now()->add($this->lastRecurringInterval->toDateInterval()));
    }

    #[Then('recurring plan is marked as failed')]
    public function recurringPlanIsMarkedAsFailed(): void
    {
        Assert::notNull($this->lastRecurringPlanId);
        $recurringPlan = $this->queryBus->ask(new GetRecurringPlan($this->lastRecurringPlanId));
        Assert::isInstanceOf($recurringPlan, RecurringPlan::class);
        Assert::eq($recurringPlan->getStatus(), RecurringPlanStatus::Failed);
        Assert::null($recurringPlan->getNextRenewalTime());
    }

    #[When('recurring plan is due for renewal')]
    public function recurringPlanIsDueForRenewal(): void
    {
        $this->clock->update($this->clock->now()->add($this->lastRecurringInterval->toDateInterval()));
        $this->delayedTransport->processOrFail();
    }

    #[Then('recurring plan renewal is initiated')]
    public function recurringPlanRenewalIsInitiated(): void
    {
        Assert::notNull($this->lastRecurringPlanId);
        $recurringPlan = $this->queryBus->ask(new GetRecurringPlan($this->lastRecurringPlanId));
        Assert::isInstanceOf($recurringPlan, RecurringPlan::class);
        Assert::eq($recurringPlan->getStatus(), RecurringPlanStatus::Active);
        Assert::notNull($recurringPlan->getRenewalInProgressDonationId());
        $this->lastDonationId = DonationId::fromString($recurringPlan->getRenewalInProgressDonationId());
    }

    #[Then('recurring plan renewal is completed')]
    public function recurringPlanRenewalIsCompleted(): void
    {
        Assert::notNull($this->lastRecurringPlanId);
        $recurringPlan = $this->queryBus->ask(new GetRecurringPlan($this->lastRecurringPlanId));
        Assert::isInstanceOf($recurringPlan, RecurringPlan::class);
        Assert::eq($recurringPlan->getStatus(), RecurringPlanStatus::Active);
        Assert::null($recurringPlan->getRenewalInProgressDonationId());
        Assert::eq($recurringPlan->getNextRenewalTime(), $this->clock->now()->add($this->lastRecurringInterval->toDateInterval()));
    }

    #[Then('recurring plan is marked as failing')]
    public function recurringPlanIsMarkedAsFailing(): void
    {
        Assert::notNull($this->lastRecurringPlanId);
        $recurringPlan = $this->queryBus->ask(new GetRecurringPlan($this->lastRecurringPlanId));
        Assert::isInstanceOf($recurringPlan, RecurringPlan::class);
        Assert::eq($recurringPlan->getStatus(), RecurringPlanStatus::Failing);
    }

    #[When('recurring plan is re-activated')]
    public function recurringPlanIsActivated(): void
    {
        Assert::notNull($this->lastRecurringPlanId);
        $this->commandBus->send(new ReActivateRecurringPlanIntegrationCommand(
            $this->lastRecurringPlanId,
        ));
    }

    // Campaign-related steps

    #[When('create campaign with name :name and public title :publicTitle')]
    public function createCampaignWithNameAndPublicTitle(string $name, string $publicTitle): void
    {
        $command = new CreateCampaign(
            new CampaignName($name),
            new CampaignPublicTitle($publicTitle)
        );
        $this->lastCampaignId = $command->campaignId;
        $this->commandBus->dispatch($command);
    }

    #[When('update campaign name to :newName')]
    public function updateCampaignNameTo(string $newName): void
    {
        Assert::notNull($this->lastCampaignId);

        $command = new UpdateCampaignName(
            $this->lastCampaignId,
            new CampaignName($newName)
        );

        $this->commandBus->dispatch($command);
    }

    #[When('update campaign public title to :newPublicTitle')]
    public function updateCampaignPublicTitleTo(string $newPublicTitle): void
    {
        Assert::notNull($this->lastCampaignId);

        $command = new UpdateCampaignPublicTitle(
            $this->lastCampaignId,
            new CampaignPublicTitle($newPublicTitle)
        );

        $this->commandBus->dispatch($command);
    }

    #[When('activate campaign')]
    public function activateCampaign(): void
    {
        Assert::notNull($this->lastCampaignId);

        $command = new ActivateCampaign($this->lastCampaignId);

        $this->commandBus->dispatch($command);
    }

    #[When('archive campaign')]
    public function archiveCampaign(): void
    {
        Assert::notNull($this->lastCampaignId);

        $command = new ArchiveCampaign($this->lastCampaignId);

        $this->commandBus->dispatch($command);
    }

    #[When('trying to archive campaign')]
    public function tryingToArchiveCampaign(): void
    {
        try {
            $this->archiveCampaign();
        } catch (Throwable $e) {
            $this->lastException = $e;
        }
    }

    #[Given('created campaign exists')]
    public function createdCampaignExists(): void
    {
        $this->createCampaignWithNameAndPublicTitle('Test Campaign', 'Test Public Title');
    }

    #[Given('created and activated campaign exists')]
    public function createdAndActivatedCampaignExists(): void
    {
        $this->createdCampaignExists();
        $this->activateCampaign();
    }

    #[Given('created, activated, and archived campaign exists')]
    public function createdActivatedAndArchivedCampaignExists(): void
    {
        $this->createdAndActivatedCampaignExists();
        $this->archiveCampaign();
    }

    #[Then('campaign is created with status :status')]
    public function campaignIsCreated(string $status): void
    {
        $campaign = $this->queryBus->ask(new GetCampaign($this->lastCampaignId));
        Assert::isInstanceOf($campaign, Campaign::class);
        Assert::notNull($campaign, 'No campaign found');
        $expectedStatus = CampaignStatus::from($status);
        Assert::same(
            $campaign->getStatus(),
            $expectedStatus,
            sprintf('Campaign status should be %s, got %s', $status, $campaign->getStatus()->value)
        );
    }

    #[Then('campaign name is updated to :newName')]
    public function campaignNameIsUpdated(string $newName): void
    {
        Assert::notNull($this->lastCampaignId);
        $campaign = $this->queryBus->ask(new GetCampaign($this->lastCampaignId));
        Assert::isInstanceOf($campaign, Campaign::class);
        Assert::eq($campaign->getName(), $newName, sprintf('Campaign name should be "%s"', $newName));
    }


    #[Then('campaign public title is updated to :newName')]
    public function campaignPublicTitleIsUpdated(string $newName): void
    {
        Assert::notNull($this->lastCampaignId);
        $campaign = $this->queryBus->ask(new GetCampaign($this->lastCampaignId));
        Assert::isInstanceOf($campaign, Campaign::class);
        Assert::eq($campaign->getPublicTitle(), $newName, sprintf('Campaign public title should be "%s"', $newName));
    }


    #[Then('campaign is activated')]
    public function campaignIsActivated(): void
    {
        Assert::notNull($this->lastCampaignId);
        $campaign = $this->queryBus->ask(new GetCampaign($this->lastCampaignId));
        Assert::isInstanceOf($campaign, Campaign::class);
        Assert::same(
            $campaign->getStatus(),
            CampaignStatus::Active,
            sprintf('Campaign status should be %s, got %s', CampaignStatus::Active->value, $campaign->getStatus()->value)
        );
    }


    #[Then('campaign is archived')]
    public function campaignIsArchived(): void
    {
        Assert::notNull($this->lastCampaignId);
        $campaign = $this->queryBus->ask(new GetCampaign($this->lastCampaignId));
        Assert::isInstanceOf($campaign, Campaign::class);
        Assert::same(
            $campaign->getStatus(),
            CampaignStatus::Archived,
            sprintf('Campaign status should be %s, got %s', CampaignStatus::Archived->value, $campaign->getStatus()->value)
        );
    }

    #[Then('operation fails with error :errorMessage')]
    public function operationFailsWithError(string $errorMessage): void
    {
        Assert::notNull($this->lastException, 'An exception should have been thrown');
        $exception = $this->lastException;
        $found = false;
        while ($exception !== null) {
            if ($exception instanceof LogicException) {
                $found = true;
                Assert::eq(
                    $exception->getMessage(),
                    $errorMessage,
                    sprintf('Exception message should be "%s", was "%s"', $errorMessage, $exception->getMessage())
                );
                break;
            }
            $exception = $exception->getPrevious();
        }

        Assert::true($found, 'A LogicException should have been thrown or in the exception chain');
    }



}
