<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentRequest;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command\InitiatePaymentIntegrationCommand;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class InitiatePaymentHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(InitiatePaymentIntegrationCommand $command): void
    {
        // Idempotency: Check if payment already initiated
        if ($this->paymentRepository->has($command->paymentId)) {
            return;
        }

        $paymentRequest = new PaymentRequest(
            $command->paymentId,
            $command->amount,
            $command->gateway,
            $command->description,
            $command->appliedTo,
            $command->email,
        );

        // Initiate payment aggregate
        $payment = Payment::initiate(
            $this->clock->now(),
            $paymentRequest,
            $command->paymentMethodAction,
        );
        try {
            $this->paymentRepository->save($payment);
        } catch (AggregateAlreadyExistsException $e) {
            // Idempotency: Another process created the payment concurrently
            return;
        }
    }
}
