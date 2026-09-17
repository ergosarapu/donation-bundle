<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationAccepted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationCreated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanActivated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanCreated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanInitiated;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\DTO\ClaimEvidenceLevel;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\DTO\ClaimPresentation;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\DTO\ClaimSource;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\DTO\SourceContext;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Event\ClaimPresentedIntegrationEvent;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\LegalIdentifier;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;

class ClaimPresentationHandler
{
    public function __construct(
        private readonly EventBusInterface $eventBus,
    ) {
    }

    public function onDonationCreated(DonationCreated $event): void
    {
        $claimSource = new ClaimSource(SourceContext::Donations, $event->donationId->toString(), 'donation.created');
        $correlatedSources = $event->recurringPlanId !== null
            ? [new ClaimSource(SourceContext::Donations, $event->recurringPlanId->toString())]
            : [];
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
            $this->eventBus->dispatch(new ClaimPresentedIntegrationEvent(
                $claimSource,
                $presentations,
                correlatedSources: $correlatedSources,
            ));
        }
    }

    public function onRecurringPlanCreated(RecurringPlanCreated $event): void
    {
        $claimSource = new ClaimSource(SourceContext::Donations, $event->recurringPlanId->toString(), 'recurring_plan.created');
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
            $this->eventBus->dispatch(new ClaimPresentedIntegrationEvent(
                $claimSource,
                $presentations,
                correlatedSources: [new ClaimSource(SourceContext::Donations, $event->initialDonationId->toString())],
            ));
        }
    }

    public function onRecurringPlanInitiated(RecurringPlanInitiated $event): void
    {
        $claimSource = new ClaimSource(SourceContext::Donations, $event->recurringPlanId->toString(), 'recurring_plan.initiated');
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
            $this->eventBus->dispatch(new ClaimPresentedIntegrationEvent(
                $claimSource,
                $presentations,
                correlatedSources: [new ClaimSource(SourceContext::Donations, $event->initialDonationId->toString())],
            ));
        }
    }

    public function onRecurringPlanActivated(RecurringPlanActivated $event): void
    {
        $claimSource = new ClaimSource(SourceContext::Donations, $event->id->toString(), 'recurring_plan.activated');
        $presentations = [];

        $presentations[] = ClaimPresentation::forType(PersonName::class, ClaimEvidenceLevel::VerifiedByUser);
        $presentations[] = ClaimPresentation::forType(Email::class, ClaimEvidenceLevel::VerifiedByUser);
        $presentations[] = ClaimPresentation::forType(LegalIdentifier::class, ClaimEvidenceLevel::VerifiedByUser);

        $this->eventBus->dispatch(new ClaimPresentedIntegrationEvent($claimSource, $presentations, correlatedSources: []));
    }

    public function onDonationInitiated(DonationInitiated $event): void
    {

        $claimSource = new ClaimSource(SourceContext::Donations, $event->donationId->toString(), 'donation.initiated');
        $correlatedSources = $event->recurringPlanId !== null
            ? [new ClaimSource(SourceContext::Donations, $event->recurringPlanId->toString())]
            : [];
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
            $this->eventBus->dispatch(new ClaimPresentedIntegrationEvent(
                $claimSource,
                $presentations,
                correlatedSources: $correlatedSources,
            ));
        }
    }

    public function onDonationAccepted(DonationAccepted $event): void
    {
        $claimSource = new ClaimSource(SourceContext::Donations, $event->donationId->toString(), 'donation.accepted');
        $this->eventBus->dispatch(new ClaimPresentedIntegrationEvent(
            $claimSource,
            [
                ClaimPresentation::forType(PersonName::class, ClaimEvidenceLevel::VerifiedByUser),
                ClaimPresentation::forType(Email::class, ClaimEvidenceLevel::VerifiedByUser),
                ClaimPresentation::forType(LegalIdentifier::class, ClaimEvidenceLevel::VerifiedByUser),
            ],
            correlatedSources: $event->recurringPlanId !== null
                ? [new ClaimSource(SourceContext::Donations, $event->recurringPlanId->toString())]
                : []
        ));
    }

}
