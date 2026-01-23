<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\InitiatePayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment;
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

    public function __invoke(InitiatePayment $command): void
    {
        // Idempotency: Check if payment already initiated
        if ($this->paymentRepository->has($command->paymentRequest->paymentId)) {
            return;
        }

        // Initiate payment aggregate
        $payment = Payment::initiate(
            $this->clock->now(),
            $command->paymentRequest,
        );
        try {
            $this->paymentRepository->save($payment);
        } catch (AggregateAlreadyExistsException $e) {
            // Idempotency: Another process created the payment concurrently
            return;
        }
    }
}
