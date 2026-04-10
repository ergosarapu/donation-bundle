<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\CreatePaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\UpdatePaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentFailed;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodAction;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodActionIntent;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUnusableReason;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class PaymentFailedHandler implements EventHandlerInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(PaymentFailed $event): void
    {
        if ($event->paymentMethodAction === null) {
            return;
        }

        match ($event->paymentMethodAction->intent) {
            PaymentMethodActionIntent::Request => $this->handleRequestIntent($event->paymentMethodAction, $event->paymentMethodAction->getCreateFor()),
            PaymentMethodActionIntent::Use => $this->handleUseIntent($event->paymentMethodAction, $event->paymentMethodResult),
        };
    }

    private function handleRequestIntent(PaymentMethodAction $action, string $createFor): void
    {
        $this->commandBus->dispatch(new CreatePaymentMethod(
            $action->paymentMethodId,
            PaymentMethodResult::unusable(PaymentMethodUnusableReason::RequestFailed),
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
