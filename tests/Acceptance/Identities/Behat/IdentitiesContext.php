<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Acceptance\Identities\Behat;

use Behat\Behat\Context\Context;
use Behat\Hook\AfterScenario;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\GetClaimBySource;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\GetIdentity;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model\Claim;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSourceContext;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\DTO\ClaimEvidenceLevel;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\DTO\ClaimPresentation;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\DTO\ClaimSource;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\DTO\SourceContext;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Event\ClaimPresentedIntegrationEvent;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\LegalIdentifier;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngine;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Webmozart\Assert\Assert;
use Zenstruck\Messenger\Test\Transport\TestTransport;

final class IdentitiesContext implements Context
{

    public function __construct(
        private readonly SubscriptionEngine $subscriptionEngine,
        private readonly EventBusInterface $eventBus,
        private readonly QueryBusInterface $queryBus,
        #[Autowire(service: 'messenger.transport.integration_event')]
        private readonly TestTransport $integrationEventTransport,
    ) {
    }

    /** @var array<string, ClaimSource> */
    private array $claimSources = [];
    private ?ClaimSource $presentedClaimSource = null;

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

    #[Given('claim ":sourceId" has no data')]
    public function claimHasNoData(string $sourceId): void
    {
        $this->claimSources[$sourceId] = $this->presentClaim(new ClaimSource(SourceContext::Donation, $sourceId));
    }

    #[Given('claim ":sourceId" has email ":email"')]
    public function claimHasEmail(string $sourceId, string $email): void
    {
        $this->claimSources[$sourceId] = $this->presentClaim(
            new ClaimSource(SourceContext::Donation, $sourceId),
            [ClaimPresentation::forValue(new Email($email), ClaimEvidenceLevel::Verified)],
        );
    }

    #[Given('claim ":sourceId" has legal identifier ":legalIdentifier"')]
    public function claimHasLegalIdentifier(string $sourceId, string $legalIdentifier): void
    {
        $this->claimSources[$sourceId] = $this->presentClaim(
            new ClaimSource(SourceContext::Donation, $sourceId),
            [ClaimPresentation::forValue(
                LegalIdentifier::nationalIdNumber($legalIdentifier),
                ClaimEvidenceLevel::Verified,
            )],
        );
    }

    #[Given('claim ":sourceId" has iban ":iban"')]
    public function claimHasIban(string $sourceId, string $iban): void
    {
        $this->claimSources[$sourceId] = $this->presentClaim(
            new ClaimSource(SourceContext::Donation, $sourceId),
            [ClaimPresentation::forValue(new Iban($iban), ClaimEvidenceLevel::Verified)],
        );
    }

    #[When('a claim is presented with correlated sources ":correlatedSources", email ":email", legal identifier ":legalIdentifier", and iban ":iban"')]
    public function claimIsPresented(
        string $correlatedSources,
        string $email,
        string $legalIdentifier,
        string $iban,
    ): void {
        $this->presentClaim(
            new ClaimSource(SourceContext::Payment, 'presented'),
            [
                ClaimPresentation::forValue(new Email($email), ClaimEvidenceLevel::Verified),
                ClaimPresentation::forValue(
                    LegalIdentifier::nationalIdNumber($legalIdentifier),
                    ClaimEvidenceLevel::Verified,
                ),
                ClaimPresentation::forValue(new Iban($iban), ClaimEvidenceLevel::Verified),
            ],
            $correlatedSources === 'no'
                ? []
                : array_map(
                    fn (string $sourceId): ClaimSource => $this->claimSources[$sourceId],
                    explode(',', $correlatedSources),
                ),
        );
    }

    #[Then('the claim belongs to identity ":identity"')]
    public function claimBelongsToIdentity(string $identity): void
    {
        $presentedClaim = $this->claimBySource($this->presentedClaimSource);
        $identityId = $presentedClaim->getIdentityId();
        Assert::notNull($identityId, 'A claim should belong to an identity.');
        Assert::notNull(
            $this->queryBus->ask(new GetIdentity($identityId)),
            sprintf('Identity "%s" should exist.', $identityId),
        );

        if ($identity === 'new') {
            Assert::same([], $presentedClaim->getConnectedClaimIds());

            return;
        }

        $expectedClaim = $this->claimBySource($this->claimSources[$identity]);

        Assert::same($presentedClaim->getIdentityId(), $expectedClaim->getIdentityId());
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

    /**
     * @param list<ClaimPresentation> $presentations
     * @param list<ClaimSource> $correlatedSources
     */
    private function presentClaim(
        ClaimSource $claimSource,
        array $presentations = [],
        array $correlatedSources = [],
    ): ClaimSource {
        $this->eventBus->dispatch(new ClaimPresentedIntegrationEvent(
            $claimSource,
            $presentations,
            $correlatedSources,
        ));
        $this->integrationEventTransport->processOrFail(1);
        $this->presentedClaimSource = $claimSource;

        return $claimSource;
    }

    private function claimBySource(?ClaimSource $source): Claim
    {
        Assert::notNull($source);
        $claim = $this->queryBus->ask(new GetClaimBySource(
            ClaimSourceContext::from($source->context->value),
            $source->id,
        ));
        Assert::isInstanceOf(
            $claim,
            Claim::class,
            sprintf('Claim for source "%s:%s" was not found.', $source->context->value, $source->id),
        );

        return $claim;
    }
}
