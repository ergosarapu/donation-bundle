<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\CompleteRecurringDonationAttempt;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationAccepted;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Event\ClaimPresentedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimerContext;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimEvidenceLevel;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimPresentation;
use ErgoSarapu\DonationBundle\IntegrationContracts\ValueObject\EntityId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\LegalIdentifier;
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
            new EntityId($event->donationId->toString()),
            ClaimerContext::Donation,
            [
                ClaimPresentation::forType(PersonName::class, ClaimEvidenceLevel::VerifiedByUser),
                ClaimPresentation::forType(Email::class, ClaimEvidenceLevel::VerifiedByUser),
                ClaimPresentation::forType(LegalIdentifier::class, ClaimEvidenceLevel::VerifiedByUser),
            ],
        ));
    }
}
