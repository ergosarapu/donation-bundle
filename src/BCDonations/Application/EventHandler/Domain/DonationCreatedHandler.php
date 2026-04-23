<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationCreated;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Event\ClaimPresentedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimerContext;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimEvidenceLevel;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimPresentation;
use ErgoSarapu\DonationBundle\IntegrationContracts\ValueObject\EntityId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

final class DonationCreatedHandler implements EventHandlerInterface
{
    public function __construct(
        private readonly EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(DonationCreated $event): void
    {
        $presentations = [];

        if ($event->donorDetails?->name !== null) {
            $presentations[] = ClaimPresentation::forValue($event->donorDetails->name, ClaimEvidenceLevel::Observed);
        }

        if ($event->donorDetails?->email !== null) {
            $presentations[] = ClaimPresentation::forValue($event->donorDetails->email, ClaimEvidenceLevel::Observed);
        }

        if ($event->donorDetails?->legalIdentifier !== null) {
            $presentations[] = ClaimPresentation::forValue($event->donorDetails->legalIdentifier, ClaimEvidenceLevel::Observed);
        }

        if ($presentations !== []) {
            $this->eventBus->dispatch(new ClaimPresentedIntegrationEvent(new EntityId($event->donationId->toString()), ClaimerContext::Donation, $presentations));
        }
    }
}
