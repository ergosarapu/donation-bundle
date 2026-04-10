<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanActionIntent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Event\ClaimPresentedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimerContext;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimEvidenceLevel;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimPresentation;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command\InitiatePaymentIntegrationCommand;
use ErgoSarapu\DonationBundle\IntegrationContracts\ValueObject\EntityId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class DonationInitiatedHandler implements EventHandlerInterface
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(DonationInitiated $event): void
    {
        $this->commandBus->dispatch(new InitiatePaymentIntegrationCommand(
            $event->amount,
            $event->gateway,
            $event->description,
            new EntityId($event->donationId->toString()),
            $event->donorDetails?->email,
            $event->recurringPlanAction?->intent === RecurringPlanActionIntent::Renew && $event->recurringPlanAction->paymentMethodId !== null
                ? new EntityId($event->recurringPlanAction->paymentMethodId)
                : null,
            $event->recurringPlanAction?->intent === RecurringPlanActionIntent::Init && $event->recurringPlanId !== null
                ? new EntityId($event->recurringPlanId->toString())
                : null,
        ));

        $claimerId = new EntityId($event->donationId->toString());
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
            $this->eventBus->dispatch(new ClaimPresentedIntegrationEvent($claimerId, ClaimerContext::Donation, $presentations));
        }
    }
}
