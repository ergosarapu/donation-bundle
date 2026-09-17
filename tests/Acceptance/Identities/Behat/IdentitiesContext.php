<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Acceptance\Identities\Behat;

use Behat\Behat\Context\Context;
use Behat\Hook\AfterScenario;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngine;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Zenstruck\Messenger\Test\Transport\TestTransport;

final class IdentitiesContext implements Context
{

    public function __construct(
        private readonly SubscriptionEngine $subscriptionEngine,
        private readonly CommandBusInterface $commandBus,
        private readonly EventBusInterface $eventBus,
        private readonly QueryBusInterface $queryBus,
        #[Autowire(service: 'messenger.transport.integration_event')]
        private readonly TestTransport $integrationEventTransport,

    ) {
        $this->initProjections();
    }

    #[BeforeScenario]
    public function resetTransports(): void
    {
        $this->integrationEventTransport->reset();
    }

    #[BeforeScenario]
    public function initProjections(): void
    {
        $this->clearProjections();
        $this->subscriptionEngine->setup();
        $this->subscriptionEngine->boot();
    }

    #[AfterScenario]
    public function clearProjections(): void
    {
        $this->subscriptionEngine->remove();
    }

    #[Given('claim ":claim" has source ":source"')]
    public function claimHasSource(string $claim, string $source): void
    {
    }

    #[Given('claim ":claim" has email ":email"')]
    public function claimHasEmail(string $claim, string $email): void
    {
    }

    #[Given('claim ":claim" has legal identifier ":legalIdentifier"')]
    public function claimHasLegalIdentifier(string $claim, string $legalIdentifier): void
    {
    }

    #[Given('claim ":claim" has iban ":iban"')]
    public function claimHasIban(string $claim, string $iban): void
    {
    }

    #[When('a claim is presented with correlated sources ":correlatedSources", email ":email", legal identifier ":legalIdentifier", and iban ":iban"')]
    public function claimIsPresented(
        string $correlatedSources,
        string $email,
        string $legalIdentifier,
        string $iban,
    ): void {
    }

    #[Then('the claim belongs to identity ":identity"')]
    public function claimBelongsToIdentity(string $identity): void
    {
    }

    #[Given('claims with sources ":sources" have correlations ":initialCorrelations"')]
    public function claimsHaveCorrelations(string $sources, string $initialCorrelations): void
    {
    }

    #[When('correlation ":correlationUpdate" is applied')]
    public function correlationIsApplied(string $correlationUpdate): void
    {
    }

    #[Then('identities contain claim groups ":identityGroups"')]
    public function identitiesContainClaimGroups(string $identityGroups): void
    {
    }
}
