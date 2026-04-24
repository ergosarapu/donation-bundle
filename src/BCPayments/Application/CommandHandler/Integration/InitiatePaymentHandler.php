<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\Integration;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\InitiatePayment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodAction;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentRequest;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command\InitiatePaymentIntegrationCommand;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;

class InitiatePaymentHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(InitiatePaymentIntegrationCommand $command): void
    {
        $paymentId = PaymentId::generate();
        $paymentMethodAction = $this->resolvePaymentMethodAction($command, $paymentId);
        $paymentRequest = new PaymentRequest(
            $paymentId,
            $command->amount,
            $command->gateway,
            $command->description,
            $command->donationId->toString(),
            $command->email,
            $paymentMethodAction,
        );
        $this->commandBus->dispatch(new InitiatePayment($paymentRequest));
    }

    private function resolvePaymentMethodAction(InitiatePaymentIntegrationCommand $command, PaymentId $paymentId): ?PaymentMethodAction
    {
        if ($command->paymentMethodId !== null) {
            return PaymentMethodAction::forUse(PaymentMethodId::fromString($command->paymentMethodId->toString()), $paymentId);
        }

        if ($command->requestPaymentMethodFor === null) {
            return null;
        }

        return PaymentMethodAction::forRequest(PaymentMethodId::generate(), $paymentId, $command->requestPaymentMethodFor->toString());
    }
}
