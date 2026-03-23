<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportPending;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Event\ClaimPresentedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimPresentation;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ClaimEvidenceLevel;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ClaimSource;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\RawName;

final class PaymentImportPendingHandler implements EventHandlerInterface
{
    public function __construct(
        private readonly EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(PaymentImportPending $event): void
    {
        $source = ClaimSource::forPayment($event->paymentId);
        $presentations = [];

        if ($event->accountHolderName !== null) {
            $presentations[] = ClaimPresentation::forValue(new RawName($event->accountHolderName->value), ClaimEvidenceLevel::Observed);
        }

        if ($event->iban !== null) {
            $presentations[] = ClaimPresentation::forValue($event->iban, ClaimEvidenceLevel::Observed);
        }

        if ($event->nationalIdCode !== null) {
            $presentations[] = ClaimPresentation::forValue($event->nationalIdCode, ClaimEvidenceLevel::Observed);
        }

        if ($presentations !== []) {
            $this->eventBus->dispatch(new ClaimPresentedIntegrationEvent($source, $presentations));
        }
    }
}
