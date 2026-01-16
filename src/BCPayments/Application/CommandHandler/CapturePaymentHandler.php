<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\CapturePayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsCaptured;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsFailed;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentGatewayInterface;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;
use RuntimeException;

class CapturePaymentHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly ClockInterface $clock,
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(CapturePayment $command): void
    {
        $paymentId = $command->paymentId;
        $payment = $this->paymentRepository->load($paymentId);
        $gatewayRequest = $payment->reserveGatewayCall($this->clock->now());
        if ($gatewayRequest === null) {
            // Idempotency check: if already reserved, do not proceed further
            return;
        }
        $this->paymentRepository->save($payment);

        $result = $this->paymentGateway->capture($gatewayRequest, $command->credentialValue);

        if ($result->isSuccess()) {
            $this->commandBus->dispatch(new MarkPaymentAsCaptured($paymentId, $result->getCapturedAmount(), $result->getPaymentMethodResult()));
            return;
        }

        if ($result->isTransientFailure()) {
            $payment->releaseGatewayCall($this->clock->now());
            $this->paymentRepository->save($payment);
            throw new RuntimeException('Transient error during payment capture, retry later.');
        }

        $this->commandBus->dispatch(new MarkPaymentAsFailed($paymentId, $result->getPaymentMethodResult()));
    }
}
