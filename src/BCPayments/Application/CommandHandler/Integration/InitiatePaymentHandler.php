<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\Integration;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\InitiatePayment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodAction;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentRequest;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command\InitiatePaymentIntegrationCommand;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodId;

class InitiatePaymentHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(InitiatePaymentIntegrationCommand $command): void
    {
        $paymentMethodAction = null;
        if ($command->requestPaymentMethodFor !== null && $command->paymentMethodId === null) {
            $paymentMethodAction = PaymentMethodAction::forRequest(PaymentMethodId::generate(), $command->paymentId, $command->requestPaymentMethodFor);
        } elseif ($command->paymentMethodId !== null) {
            $paymentMethodAction = PaymentMethodAction::forUse($command->paymentMethodId, $command->paymentId);
        }
        $paymentRequest = new PaymentRequest(
            $command->paymentId,
            $command->amount,
            $command->gateway,
            $command->description,
            $command->appliedTo,
            $command->email,
            $paymentMethodAction,
        );
        $this->commandBus->dispatch(new InitiatePayment($paymentRequest));
    }
}
