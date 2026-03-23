<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\CompleteRecurringDonationAttempt;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationAccepted;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Event\ClaimPresentedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimPresentation;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ClaimEvidenceLevel;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ClaimSource;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;

class DonationAcceptedHandler implements EventHandlerInterface
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(DonationAccepted $event): void
    {
        if ($event->recurringPlanId !== null) {
            $this->commandBus->dispatch(new CompleteRecurringDonationAttempt(
                $event->recurringPlanId,
                $event->donationId,
                $event->status
            ));
        }

        $this->eventBus->dispatch(new ClaimPresentedIntegrationEvent(
            ClaimSource::forDonation($event->donationId),
            [
                ClaimPresentation::forType(PersonName::class, ClaimEvidenceLevel::VerifiedByUser),
                ClaimPresentation::forType(Email::class, ClaimEvidenceLevel::VerifiedByUser),
                ClaimPresentation::forType(NationalIdCode::class, ClaimEvidenceLevel::VerifiedByUser),
            ],
        ));
    }
}
