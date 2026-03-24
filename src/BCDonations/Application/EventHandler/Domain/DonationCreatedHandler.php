<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationCreated;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Event\ClaimPresentedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimPresentation;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ClaimEvidenceLevel;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ClaimSource;

final class DonationCreatedHandler implements EventHandlerInterface
{
    public function __construct(
        private readonly EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(DonationCreated $event): void
    {
        $source = ClaimSource::forDonation($event->donationId);
        $presentations = [];

        if ($event->donorDetails?->name !== null) {
            $presentations[] = ClaimPresentation::forValue($event->donorDetails->name, ClaimEvidenceLevel::Observed);
        }

        if ($event->donorDetails?->email !== null) {
            $presentations[] = ClaimPresentation::forValue($event->donorDetails->email, ClaimEvidenceLevel::Observed);
        }

        if ($event->donorDetails?->nationalIdCode !== null) {
            $presentations[] = ClaimPresentation::forValue($event->donorDetails->nationalIdCode, ClaimEvidenceLevel::Observed);
        }

        if ($presentations !== []) {
            $this->eventBus->dispatch(new ClaimPresentedIntegrationEvent($source, $presentations));
        }
    }
}
