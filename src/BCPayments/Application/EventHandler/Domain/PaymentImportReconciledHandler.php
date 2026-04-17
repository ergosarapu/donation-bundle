<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportReconciled;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Event\ClaimPresentedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimerContext;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimEvidenceLevel;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimPresentation;
use ErgoSarapu\DonationBundle\IntegrationContracts\ValueObject\EntityId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\OrganisationRegCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\RawName;

final class PaymentImportReconciledHandler implements EventHandlerInterface
{
    public function __construct(
        private readonly EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(PaymentImportReconciled $event): void
    {
        $this->eventBus->dispatch(new ClaimPresentedIntegrationEvent(
            new EntityId($event->paymentId->toString()),
            ClaimerContext::Payment,
            [
                ClaimPresentation::forType(RawName::class, ClaimEvidenceLevel::Verified),
                ClaimPresentation::forType(Iban::class, ClaimEvidenceLevel::Verified),
                ClaimPresentation::forType(NationalIdCode::class, ClaimEvidenceLevel::Verified),
                ClaimPresentation::forType(OrganisationRegCode::class, ClaimEvidenceLevel::Verified),
            ],
        ));
    }
}
