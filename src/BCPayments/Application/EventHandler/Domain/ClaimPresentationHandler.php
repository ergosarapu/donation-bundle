<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCaptured;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCreated;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportAccepted;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportPending;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportReconciled;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportRejected;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentInitiated;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\DTO\ClaimEvidenceLevel;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\DTO\ClaimPresentation;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\DTO\ClaimSource;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\DTO\SourceContext;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Event\ClaimPresentedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Event\ClaimSourceDataInvalidatedIntegrationEvent;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\LegalIdentifier;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\RawName;

class ClaimPresentationHandler
{
    public function __construct(
        private readonly EventBusInterface $eventBus,
    ) {
    }

    public function onPaymentInitiated(PaymentInitiated $event): void
    {
        $claimSource = new ClaimSource(SourceContext::Payment, $event->paymentId->toString(), 'payment.initiated');
        $correlatedSources = [new ClaimSource(SourceContext::Donation, $event->donationId)];
        $presentations = [];

        if ($event->email !== null) {
            $presentations[] = ClaimPresentation::forValue($event->email, ClaimEvidenceLevel::Observed);
        }

        if ($presentations !== []) {
            $this->eventBus->dispatch(new ClaimPresentedIntegrationEvent(
                $claimSource,
                $presentations,
                correlatedSources: $correlatedSources,
            ));
        }
    }

    public function onPaymentCreated(PaymentCreated $event): void
    {
        $claimSource = new ClaimSource(SourceContext::Payment, $event->paymentId->toString(), 'payment.created');
        $correlatedSources = $event->donationId !== null
            ? [new ClaimSource(SourceContext::Donation, $event->donationId)]
            : [];
        $presentations = [];

        if ($event->name !== null) {
            $presentations[] = ClaimPresentation::forValue($event->name, ClaimEvidenceLevel::Observed);
        }

        if ($event->email !== null) {
            $presentations[] = ClaimPresentation::forValue($event->email, ClaimEvidenceLevel::Observed);
        }

        if ($event->legalIdentifier !== null) {
            $presentations[] = ClaimPresentation::forValue($event->legalIdentifier, ClaimEvidenceLevel::Observed);
        }

        if ($event->iban !== null) {
            $presentations[] = ClaimPresentation::forValue($event->iban, ClaimEvidenceLevel::Observed);
        }

        if ($presentations !== []) {
            $this->eventBus->dispatch(new ClaimPresentedIntegrationEvent(
                $claimSource,
                $presentations,
                correlatedSources: $correlatedSources,
            ));
        }
    }

    public function onPaymentCaptured(PaymentCaptured $event): void
    {
        $claimSource = new ClaimSource(SourceContext::Payment, $event->paymentId->toString(), 'payment.captured');
        $correlatedSources = $event->donationId !== null
            ? [new ClaimSource(SourceContext::Donation, $event->donationId)]
            : [];
        $presentations = [];

        if ($event->iban !== null) {
            $presentations[] = ClaimPresentation::forValue($event->iban, ClaimEvidenceLevel::Verified);
        }
        $presentations[] = ClaimPresentation::forType(Email::class, ClaimEvidenceLevel::VerifiedByUser);
        $this->eventBus->dispatch(new ClaimPresentedIntegrationEvent(
            $claimSource,
            $presentations,
            correlatedSources: $correlatedSources,
        ));
    }

    public function onPaymentImportPending(PaymentImportPending $event): void
    {
        $claimSource = new ClaimSource(SourceContext::Payment, $event->paymentId->toString(), 'payment.import_pending');
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
            $this->eventBus->dispatch(new ClaimPresentedIntegrationEvent($claimSource, $presentations, []));
        }

    }

    public function onPaymentImportAccepted(PaymentImportAccepted $event): void
    {
        $claimSource = new ClaimSource(SourceContext::Payment, $event->paymentId->toString(), 'payment.import_accepted');
        $presentations = [
            ClaimPresentation::forType(RawName::class, ClaimEvidenceLevel::Verified),
            ClaimPresentation::forType(Iban::class, ClaimEvidenceLevel::Verified),
            ClaimPresentation::forType(LegalIdentifier::class, ClaimEvidenceLevel::Verified),
        ];
        $this->eventBus->dispatch(new ClaimPresentedIntegrationEvent($claimSource, $presentations, []));
    }

    public function onPaymentImportReconciled(PaymentImportReconciled $event): void
    {
        $claimSource = new ClaimSource(SourceContext::Payment, $event->paymentId->toString(), 'payment.import_reconciled');
        $presentations = [
            ClaimPresentation::forType(RawName::class, ClaimEvidenceLevel::Verified),
            ClaimPresentation::forType(Iban::class, ClaimEvidenceLevel::Verified),
            ClaimPresentation::forType(LegalIdentifier::class, ClaimEvidenceLevel::Verified),
        ];
        $this->eventBus->dispatch(new ClaimPresentedIntegrationEvent(
            $claimSource,
            $presentations,
            correlatedSources: [new ClaimSource(SourceContext::Payment, $event->reconciledWith->toString())]
        ));
    }

    public function onPaymentImportRejected(PaymentImportRejected $event): void
    {
        $claimSource = new ClaimSource(SourceContext::Payment, $event->paymentId->toString(), 'payment.import_rejected');
        $this->eventBus->dispatch(new ClaimSourceDataInvalidatedIntegrationEvent($claimSource));
    }
}
