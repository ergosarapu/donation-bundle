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
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetInitiatedDonation;
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
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonorDetails;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringInterval;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanStatus;
use ErgoSarapu\DonationBundle\IntegrationContracts\Donations\Command\InitiateDonationIntegrationCommand;
use ErgoSarapu\DonationBundle\IntegrationContracts\Donations\Command\ReActivateRecurringPlanIntegrationCommand;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command\InitiatePaymentIntegrationCommand;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentDidNotSucceedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentMethodUnusableIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentSucceededIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\UnusablePaymentMethodCreatedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\UsablePaymentMethodCreatedIntegrationEvent;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ExternalEntityId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
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
    private ?RecurringPlanId $lastRecurringPlanId;
    private ExternalEntityId $lastPaymentMethodId;
    private RecurringInterval $lastRecurringInterval;
    private FrozenClock $clock;
    private CampaignId $lastCampaignId;
    private ?Throwable $lastException = null;

    public function __construct(
        #[Autowire(service: 'messenger.transport.delayed')]
        private readonly TestTransport $delayedTransport,
        #[Autowire(service: 'messenger.transport.integration_command')]
        private readonly TestTransport $integrationCommandTransport,
        #[Autowire(service: 'messenger.transport.integration_event')]
        private readonly TestTransport $integrationEventTransport,
        private readonly CommandBusInterface $commandBus,
        private readonly EventBusInterface $eventBus,
        private readonly QueryBusInterface $queryBus,
        private readonly SubscriptionEngine $subscriptionEngine,
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
        $this->integrationCommandTransport->reset();
        $this->integrationEventTransport->reset();
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
            new DonorDetails(),
            new ShortDescription('Test donation'),
        );
        $initiateDonation = new InitiateDonationIntegrationCommand($donationRequest);
        $this->commandBus->dispatch($initiateDonation);
        $this->integrationCommandTransport->processOrFail(1);
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
            new DonorDetails(
                new Email('example@example.com')
            ),
            new ShortDescription('Test donation'),
        );
        $this->lastRecurringInterval = new RecurringInterval(RecurringInterval::Monthly);
        $initiateRecurringDonation = new InitiateDonationIntegrationCommand($donationRequest, $this->lastRecurringInterval);
        $this->commandBus->dispatch($initiateRecurringDonation);
        $this->integrationCommandTransport->processOrFail(1);
    }


    #[Then('donation is initiated')]
    public function donationIsInitiated(): void
    {
        $donation = $this->queryBus->ask(new GetInitiatedDonation($this->lastDonationId));
        Assert::isInstanceOf($donation, Donation::class);
        $this->lastRecurringPlanId = null;
    }

    #[Then('donation is initiated for the recurring plan')]
    public function donationIsInitiatedForTheRecurringPlan(): void
    {
        $donation = $this->queryBus->ask(new GetInitiatedDonation($this->lastDonationId));
        Assert::isInstanceOf($donation, Donation::class);
        Assert::notNull($recurringPlanId = $donation->getRecurringPlanId());
        $this->lastRecurringPlanId = RecurringPlanId::fromString($recurringPlanId);
    }

    #[Then('initiate payment integration command is sent')]
    public function paymentIntegrationCommandIsSent(): void
    {
        $this->integrationCommandTransport->queue()->assertCount(1);
        $this->integrationCommandTransport->queue()->assertContains(InitiatePaymentIntegrationCommand::class);
    }

    #[Then('initiate payment integration command is sent with request to store payment method')]
    public function initiatePaymentIntegrationCommandIsSentWithRequestToStorePaymentMethod(): void
    {
        $this->integrationCommandTransport->queue()->assertContains(InitiatePaymentIntegrationCommand::class, 1);
        /** @var InitiatePaymentIntegrationCommand $command */
        $command = $this->integrationCommandTransport->queue()->first(InitiatePaymentIntegrationCommand::class)->getMessage();
        $this->integrationCommandTransport->reset();
        Assert::null($command->paymentMethodId);
        Assert::notNull($command->requestPaymentMethodFor);
        Assert::eq($command->requestPaymentMethodFor->toString(), $this->lastRecurringPlanId?->toString());
    }

    #[Then('initiate payment integration command is sent with request to use payment method')]
    public function initiatePaymentIntegrationCommandIsSentWithRequestToUsePaymentMethod(): void
    {
        $this->integrationCommandTransport->queue()->assertContains(InitiatePaymentIntegrationCommand::class, 1);
        /** @var InitiatePaymentIntegrationCommand $command */
        $command = $this->integrationCommandTransport->queue()->first(InitiatePaymentIntegrationCommand::class)->getMessage();
        $this->integrationCommandTransport->reset();
        Assert::notNull($command->paymentMethodId);
    }

    #[Then('recurring plan is initiated with the donation as initial donation')]
    public function recurringPlanIsInitiatedWithTheDonationAsInitialDonation(): void
    {
        Assert::notNull($this->lastRecurringPlanId);
        $recurringPlan = $this->queryBus->ask(new GetRecurringPlan($this->lastRecurringPlanId));
        Assert::isInstanceOf($recurringPlan, RecurringPlan::class);
        Assert::eq($recurringPlan->getInitialDonationId(), $this->lastDonationId->toString());
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
        $this->integrationEventTransport->reset();
        $this->eventBus->dispatch(new PaymentSucceededIntegrationEvent(
            ExternalEntityId::generate(),
            $this->getDefaultTestMoney(),
            ExternalEntityId::fromString($this->lastDonationId->toString()),
        ));
        $this->integrationEventTransport->processOrFail(1);
    }

    #[When('payment fails')]
    public function paymentFails(): void
    {
        $this->integrationEventTransport->reset();
        $this->eventBus->dispatch(new PaymentDidNotSucceedIntegrationEvent(
            ExternalEntityId::generate(),
            ExternalEntityId::fromString($this->lastDonationId->toString()),
        ));
        $this->integrationEventTransport->processOrFail(1);
    }

    #[Then('donation is marked as accepted')]
    public function donationIsAccepted(): void
    {
        $donation = $this->queryBus->ask(new GetDonation($this->lastDonationId));
        Assert::isInstanceOf($donation, Donation::class);
        Assert::eq($donation->getStatus(), DonationStatus::Accepted);
    }

    #[Then('donation is marked as failed')]
    public function donationIsFailed(): void
    {
        $donation = $this->queryBus->ask(new GetDonation($this->lastDonationId));
        Assert::isInstanceOf($donation, Donation::class);
        Assert::eq($donation->getStatus(), DonationStatus::Failed);
    }

    #[Given('initiated recurring plan exists')]
    public function initiatedRecurringPlanExists(): void
    {
        $this->initiateRecurringDonation();
        $this->donationIsInitiatedForTheRecurringPlan();
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
        Assert::notNull($this->lastRecurringPlanId);
        $this->integrationEventTransport->reset();
        $this->eventBus->dispatch(new PaymentMethodUnusableIntegrationEvent(
            $this->lastPaymentMethodId,
            ExternalEntityId::fromString($this->lastRecurringPlanId->toString()),
        ));
        $this->integrationEventTransport->processOrFail(1);
    }

    #[When('usable payment method is created')]
    public function usablePaymentMethodIsCreated(): void
    {
        $this->lastPaymentMethodId = ExternalEntityId::generate();
        Assert::notNull($this->lastRecurringPlanId);
        $this->integrationEventTransport->reset();
        $this->eventBus->dispatch(new UsablePaymentMethodCreatedIntegrationEvent(
            $this->lastPaymentMethodId,
            ExternalEntityId::fromString($this->lastRecurringPlanId->toString()),
        ));
        $this->integrationEventTransport->processOrFail(1);
    }


    #[When('unusable payment method is created')]
    public function unusablePaymentMethodIsCreated(): void
    {
        $this->lastPaymentMethodId = ExternalEntityId::generate();
        Assert::notNull($this->lastRecurringPlanId);
        $this->integrationEventTransport->reset();
        $this->eventBus->dispatch(new UnusablePaymentMethodCreatedIntegrationEvent(
            $this->lastPaymentMethodId,
            ExternalEntityId::fromString($this->lastRecurringPlanId->toString()),
        ));
        $this->integrationEventTransport->processOrFail(1);
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
        $this->integrationCommandTransport->reset();
        $this->commandBus->dispatch(new ReActivateRecurringPlanIntegrationCommand(
            $this->lastRecurringPlanId,
        ));
        $this->integrationCommandTransport->processOrFail(1);
    }

    // Campaign-related steps

    #[When('create campaign with name :name and public title :publicTitle')]
    public function createCampaignWithNameAndPublicTitle(string $name, string $publicTitle): void
    {
        $command = new CreateCampaign(
            new CampaignName($name),
            new CampaignPublicTitle($publicTitle),
            new ShortDescription('Default donation description')
        );
        $this->lastCampaignId = $command->campaignId;
        $this->commandBus->dispatch($command);
    }

    #[When('update campaign name to :newName')]
    public function updateCampaignNameTo(string $newName): void
    {
        $command = new UpdateCampaignName(
            $this->lastCampaignId,
            new CampaignName($newName)
        );

        $this->commandBus->dispatch($command);
    }

    #[When('update campaign public title to :newPublicTitle')]
    public function updateCampaignPublicTitleTo(string $newPublicTitle): void
    {
        $command = new UpdateCampaignPublicTitle(
            $this->lastCampaignId,
            new CampaignPublicTitle($newPublicTitle)
        );

        $this->commandBus->dispatch($command);
    }

    #[When('activate campaign')]
    public function activateCampaign(): void
    {
        $command = new ActivateCampaign($this->lastCampaignId);

        $this->commandBus->dispatch($command);
    }

    #[When('archive campaign')]
    public function archiveCampaign(): void
    {
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
        $campaign = $this->queryBus->ask(new GetCampaign($this->lastCampaignId));
        Assert::isInstanceOf($campaign, Campaign::class);
        Assert::eq($campaign->getName(), $newName, sprintf('Campaign name should be "%s"', $newName));
    }


    #[Then('campaign public title is updated to :newName')]
    public function campaignPublicTitleIsUpdated(string $newName): void
    {
        $campaign = $this->queryBus->ask(new GetCampaign($this->lastCampaignId));
        Assert::isInstanceOf($campaign, Campaign::class);
        Assert::eq($campaign->getPublicTitle(), $newName, sprintf('Campaign public title should be "%s"', $newName));
    }


    #[Then('campaign is activated')]
    public function campaignIsActivated(): void
    {
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
