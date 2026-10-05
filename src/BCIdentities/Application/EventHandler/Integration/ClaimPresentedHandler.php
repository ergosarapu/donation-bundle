<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\EventHandler\Integration;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\PresentClaimEvidence;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimEvidenceLevel;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\DTO\ClaimPresentation;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Event\ClaimPresentedIntegrationEvent;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\LegalIdentifier;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\RawName;

final class ClaimPresentedHandler implements EventHandlerInterface
{
    public function __construct(
        private readonly ClaimSourceMapper $claimSourceMapper,
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(ClaimPresentedIntegrationEvent $event): void
    {
        $simplePresentations = $this->mapPresentations($event->presentations);

        $this->commandBus->dispatch(new PresentClaimEvidence(
            claimSource: $this->claimSourceMapper->map($event->claimSource),
            presentations: $simplePresentations,
            correlatedSources: $this->claimSourceMapper->mapMany($event->correlatedSources),
        ));
    }

    /**
     * @param array<ClaimPresentation> $presentations
     * @return array{0: PersonName|RawName|Email|Iban|LegalIdentifier|class-string, 1: ClaimEvidenceLevel}[]
     */
    private function mapPresentations(array $presentations): array
    {
        $result = [];
        foreach ($presentations as $presentation) {
            $result[] = [
                $presentation->value,
                ClaimEvidenceLevel::from($presentation->evidenceLevel->value),
            ];
        }
        return $result;
    }
}
