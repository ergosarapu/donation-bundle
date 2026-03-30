<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\CreatePaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\UpdatePaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCaptured;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodAction;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodActionIntent;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUnusableReason;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Event\ClaimPresentedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimPresentation;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ExternalEntityId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ClaimEvidenceLevel;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ClaimSource;

class PaymentCapturedHandler implements EventHandlerInterface
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(PaymentCaptured $event): void
    {
        if ($event->paymentMethodAction !== null) {
            match ($event->paymentMethodAction->intent) {
                PaymentMethodActionIntent::Request => $this->handleRequestIntent($event->paymentMethodAction, $event->paymentMethodResult, $event->paymentMethodAction->getCreateFor()),
                PaymentMethodActionIntent::Use => $this->handleUseIntent($event->paymentMethodAction, $event->paymentMethodResult),
            };
        }

        if ($event->iban !== null) {
            $this->eventBus->dispatch(new ClaimPresentedIntegrationEvent(
                ClaimSource::forPayment($event->paymentId),
                [ClaimPresentation::forValue($event->iban, ClaimEvidenceLevel::Verified)],
            ));
        }
    }

    private function handleRequestIntent(
        PaymentMethodAction $action,
        ?PaymentMethodResult $paymentMethodResult,
        ExternalEntityId $createFor
    ): void {
        if ($paymentMethodResult === null) {
            $paymentMethodResult = PaymentMethodResult::unusable(PaymentMethodUnusableReason::RequestFailed);
        }
        $this->commandBus->dispatch(new CreatePaymentMethod(
            $action->paymentMethodId,
            $paymentMethodResult,
            $createFor,
        ));
    }

    private function handleUseIntent(
        PaymentMethodAction $action,
        ?PaymentMethodResult $paymentMethodResult
    ): void {
        if ($paymentMethodResult === null) {
            return;
        }

        $this->commandBus->dispatch(new UpdatePaymentMethod(
            $action->paymentMethodId,
            $paymentMethodResult,
        ));
    }
}
