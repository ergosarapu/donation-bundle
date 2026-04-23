<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportPending;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Event\ClaimPresentedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimerContext;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimEvidenceLevel;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimPresentation;
use ErgoSarapu\DonationBundle\IntegrationContracts\ValueObject\EntityId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\RawName;

final class PaymentImportPendingHandler implements EventHandlerInterface
{
    public function __construct(
        private readonly EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(PaymentImportPending $event): void
    {
        $claimerId = new EntityId($event->paymentId->toString());
        $presentations = [];

        if ($event->accountHolderName !== null) {
            $presentations[] = ClaimPresentation::forValue(new RawName($event->accountHolderName->value), ClaimEvidenceLevel::Observed);
        }

        if ($event->iban !== null) {
            $presentations[] = ClaimPresentation::forValue($event->iban, ClaimEvidenceLevel::Observed);
        }

        if ($event->legalIdentifier !== null) {
            $presentations[] = ClaimPresentation::forValue($event->legalIdentifier, ClaimEvidenceLevel::Observed);
        }

        if ($presentations !== []) {
            $this->eventBus->dispatch(new ClaimPresentedIntegrationEvent($claimerId, ClaimerContext::Payment, $presentations));
        }
    }
}
