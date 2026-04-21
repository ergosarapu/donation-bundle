<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Acceptance\Identities\Behat;

use Behat\Behat\Context\Context;
use Behat\Hook\AfterScenario;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\PresentClaimEvidence;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\GetClaim;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\GetClaimByTrackingId;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model\Claim;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimId;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimReviewReason;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSource;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Event\ClaimPresentedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimerContext;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimEvidenceLevel;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimPresentation;
use ErgoSarapu\DonationBundle\IntegrationContracts\ValueObject\EntityId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\OrganisationRegCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\RawName;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngine;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngineCriteria;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Webmozart\Assert\Assert;
use Zenstruck\Messenger\Test\Transport\TestTransport;

class IdentitiesContext implements Context
{
    private ClaimId $lastClaimId;
    private ?string $lastPreExistingIdentityId = null;

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

    #[Given('no Identity exists')]
    public function noIdentityExists(): void
    {
        // Clean state is ensured by BeforeScenario
    }

    #[Given('an Identity with email :email exists')]
    public function anIdentityWithEmailExists(string $email): void
    {
        $source = $this->createPreExistingIdentity([
            ClaimPresentation::forValue(new Email($email), ClaimEvidenceLevel::VerifiedByUser),
        ]);
        $this->lastPreExistingIdentityId = $this->findResolvedIdentityId($source);
    }

    #[Given('an Identity with email :email and national id code :nationalIdCode exists')]
    public function anIdentityWithEmailAndNationalIdCodeExists(string $email, string $nationalIdCode): void
    {
        $source = $this->createPreExistingIdentity([
            ClaimPresentation::forValue(new Email($email), ClaimEvidenceLevel::VerifiedByUser),
            ClaimPresentation::forValue(new NationalIdCode($nationalIdCode), ClaimEvidenceLevel::VerifiedByUser),
        ]);
        $this->lastPreExistingIdentityId = $this->findResolvedIdentityId($source);
    }

    #[Given('an Identity with email :email and person name :givenName :familyName exists')]
    public function anIdentityWithEmailAndPersonNameExists(string $email, string $givenName, string $familyName): void
    {
        $source = $this->createPreExistingIdentity([
            ClaimPresentation::forValue(new Email($email), ClaimEvidenceLevel::VerifiedByUser),
            ClaimPresentation::forValue(new PersonName($givenName, $familyName), ClaimEvidenceLevel::VerifiedByUser),
        ]);
        $this->lastPreExistingIdentityId = $this->findResolvedIdentityId($source);
    }

    #[Given('an Identity with email :email, national id code :nationalIdCode and person name :givenName :familyName exists')]
    public function anIdentityWithEmailNationalIdCodeAndPersonNameExists(string $email, string $nationalIdCode, string $givenName, string $familyName): void
    {
        $source = $this->createPreExistingIdentity([
            ClaimPresentation::forValue(new Email($email), ClaimEvidenceLevel::VerifiedByUser),
            ClaimPresentation::forValue(new NationalIdCode($nationalIdCode), ClaimEvidenceLevel::VerifiedByUser),
            ClaimPresentation::forValue(new PersonName($givenName, $familyName), ClaimEvidenceLevel::VerifiedByUser),
        ]);
        $this->lastPreExistingIdentityId = $this->findResolvedIdentityId($source);
    }

    #[Given('another Identity with iban :iban exists')]
    public function anotherIdentityWithIbanExists(string $iban): void
    {
        $this->createPreExistingIdentity([
            ClaimPresentation::forValue(new Iban($iban), ClaimEvidenceLevel::VerifiedByUser),
        ]);
    }

    #[Given('an Identity with email :email and org reg code :orgRegCode exists')]
    public function anIdentityWithEmailAndOrgRegCodeExists(string $email, string $orgRegCode): void
    {
        $source = $this->createPreExistingIdentity([
            ClaimPresentation::forValue(new Email($email), ClaimEvidenceLevel::VerifiedByUser),
            ClaimPresentation::forValue(new OrganisationRegCode($orgRegCode), ClaimEvidenceLevel::VerifiedByUser),
        ]);
        $this->lastPreExistingIdentityId = $this->findResolvedIdentityId($source);
    }

    #[Given('identity projection is not updating')]
    public function identityProjectionIsNotUpdating(): void
    {
        $this->subscriptionEngine->remove(new SubscriptionEngineCriteria(ids: ['identity']));
    }

    #[When('a Claim with email :email and org reg code :orgRegCode is presented with sufficient evidence')]
    public function aClaimWithEmailAndOrgRegCodeIsPresentedWithSufficientEvidence(string $email, string $orgRegCode): void
    {
        $this->presentClaim([
            ClaimPresentation::forValue(new Email($email), ClaimEvidenceLevel::VerifiedByUser),
            ClaimPresentation::forValue(new OrganisationRegCode($orgRegCode), ClaimEvidenceLevel::VerifiedByUser),
        ]);
    }

    #[When('a Claim with email :email is presented with sufficient evidence')]
    public function aClaimWithEmailIsPresentedWithSufficientEvidence(string $email): void
    {
        $this->presentClaim([
            ClaimPresentation::forValue(new Email($email), ClaimEvidenceLevel::VerifiedByUser),
        ]);
    }

    #[When('a Claim with email :email and national id code :nationalIdCode is presented with sufficient evidence')]
    public function aClaimWithEmailAndNationalIdCodeIsPresentedWithSufficientEvidence(string $email, string $nationalIdCode): void
    {
        $this->presentClaim([
            ClaimPresentation::forValue(new Email($email), ClaimEvidenceLevel::VerifiedByUser),
            ClaimPresentation::forValue(new NationalIdCode($nationalIdCode), ClaimEvidenceLevel::VerifiedByUser),
        ]);
    }

    #[When('a Claim with email :email and person name :givenName :familyName is presented with sufficient evidence')]
    public function aClaimWithEmailAndPersonNameIsPresentedWithSufficientEvidence(string $email, string $givenName, string $familyName): void
    {
        $this->presentClaim([
            ClaimPresentation::forValue(new Email($email), ClaimEvidenceLevel::VerifiedByUser),
            ClaimPresentation::forValue(new PersonName($givenName, $familyName), ClaimEvidenceLevel::VerifiedByUser),
        ]);
    }

    #[When('a Claim with email :email, iban :iban, national id code :nationalIdCode, person name :givenName :familyName and raw name :rawName is presented with sufficient evidence')]
    public function aClaimWithAllFieldsIsPresentedWithSufficientEvidence(string $email, string $iban, string $nationalIdCode, string $givenName, string $familyName, string $rawName): void
    {
        $this->presentClaim([
            ClaimPresentation::forValue(new Email($email), ClaimEvidenceLevel::VerifiedByUser),
            ClaimPresentation::forValue(new Iban($iban), ClaimEvidenceLevel::VerifiedByUser),
            ClaimPresentation::forValue(new NationalIdCode($nationalIdCode), ClaimEvidenceLevel::VerifiedByUser),
            ClaimPresentation::forValue(new PersonName($givenName, $familyName), ClaimEvidenceLevel::VerifiedByUser),
            ClaimPresentation::forValue(new RawName($rawName), ClaimEvidenceLevel::VerifiedByUser),
        ]);
    }

    #[When('a Claim with email :email, national id code :nationalIdCode and person name :givenName :familyName is presented with sufficient evidence')]
    public function aClaimWithEmailNationalIdCodeAndPersonNameIsPresentedWithSufficientEvidence(string $email, string $nationalIdCode, string $givenName, string $familyName): void
    {
        $this->presentClaim([
            ClaimPresentation::forValue(new Email($email), ClaimEvidenceLevel::VerifiedByUser),
            ClaimPresentation::forValue(new NationalIdCode($nationalIdCode), ClaimEvidenceLevel::VerifiedByUser),
            ClaimPresentation::forValue(new PersonName($givenName, $familyName), ClaimEvidenceLevel::VerifiedByUser),
        ]);
    }

    #[When('a Claim with email :email and iban :iban is presented with sufficient evidence')]
    public function aClaimWithEmailAndIbanIsPresentedWithSufficientEvidence(string $email, string $iban): void
    {
        $this->presentClaim([
            ClaimPresentation::forValue(new Email($email), ClaimEvidenceLevel::VerifiedByUser),
            ClaimPresentation::forValue(new Iban($iban), ClaimEvidenceLevel::VerifiedByUser),
        ]);
    }

    #[Then('Claim is resolved')]
    public function claimIsResolved(): void
    {
        $claim = $this->queryBus->ask(new GetClaim($this->lastClaimId));
        Assert::isInstanceOf($claim, Claim::class, 'Claim projection not found');
        Assert::true($claim->isResolved(), 'Claim should be resolved');
    }

    #[Then('a new Identity is created')]
    public function aNewIdentityIsCreated(): void
    {
        $claim = $this->queryBus->ask(new GetClaim($this->lastClaimId));
        Assert::isInstanceOf($claim, Claim::class, 'Claim projection not found');
        Assert::notNull($claim->getIdentityId(), 'Claim should be resolved to a new Identity');
        Assert::notEq($claim->getIdentityId(), $this->lastPreExistingIdentityId, 'Claim should be resolved to a new Identity, not an existing one');
        $this->lastPreExistingIdentityId = $claim->getIdentityId();
    }

    #[Then('Claim is merged into existing Identity')]
    public function claimIsMergedIntoExistingIdentity(): void
    {
        $claim = $this->queryBus->ask(new GetClaim($this->lastClaimId));
        Assert::isInstanceOf($claim, Claim::class, 'Claim projection not found');
        Assert::eq($claim->getIdentityId(), $this->lastPreExistingIdentityId, 'Claim should be merged into the pre-existing Identity');
    }

    #[Then('Claim is marked for review')]
    public function claimIsMarkedForReview(): void
    {
        $claim = $this->queryBus->ask(new GetClaim($this->lastClaimId));
        Assert::isInstanceOf($claim, Claim::class, 'Claim projection not found');
        Assert::true($claim->isInReview(), 'Claim should be in review');
    }

    #[Then('Claim review reason is merge conflict')]
    public function claimReviewReasonIsMergeConflict(): void
    {
        $claim = $this->queryBus->ask(new GetClaim($this->lastClaimId));
        Assert::isInstanceOf($claim, Claim::class, 'Claim projection not found');
        Assert::eq($claim->getReviewReason(), ClaimReviewReason::MergeConflict->value);
    }

    #[Then('Claim review reason is multiple identity matches')]
    public function claimReviewReasonIsMultipleIdentityMatches(): void
    {
        $claim = $this->queryBus->ask(new GetClaim($this->lastClaimId));
        Assert::isInstanceOf($claim, Claim::class, 'Claim projection not found');
        Assert::eq($claim->getReviewReason(), ClaimReviewReason::MultipleIdentityMatches->value);
    }

    /**
     * @param list<ClaimPresentation> $presentations
     */
    private function presentClaim(array $presentations): void
    {
        $claimerId = Uuid::uuid7()->toString();
        $this->integrationEventTransport->reset();
        $trackingId = $this->eventBus->dispatch(new ClaimPresentedIntegrationEvent(
            new EntityId($claimerId),
            ClaimerContext::Donation,
            $presentations,
        ));
        $this->integrationEventTransport->processOrFail(1);
        $claim = $this->queryBus->ask(new GetClaimByTrackingId($trackingId));
        Assert::isInstanceOf($claim, Claim::class, 'Claim projection not found for tracking ID: ' . $trackingId);
        $this->lastClaimId = ClaimId::fromString($claim->getClaimId());
    }

    /**
     * @param list<ClaimPresentation> $presentations
     */
    private function createPreExistingIdentity(array $presentations): ClaimSource
    {
        $source = ClaimSource::forDonation(Uuid::uuid7()->toString());
        $this->commandBus->dispatch(new PresentClaimEvidence(
            source: $source,
            presentations: $presentations,
        ));
        return $source;
    }

    private function findResolvedIdentityId(ClaimSource $source): string
    {
        $claimId = ClaimId::generate($source);
        $claim = $this->queryBus->ask(new GetClaim($claimId));
        Assert::isInstanceOf($claim, Claim::class, 'Pre-existing Claim projection not found');
        Assert::notNull($claim->getIdentityId(), 'Pre-existing Claim should be resolved to an Identity');
        return $claim->getIdentityId();
    }
}
