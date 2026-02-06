<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\CreatePayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class CreatePaymentHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(CreatePayment $command): void
    {
        // Idempotency: Check if payment already exists
        if ($this->paymentRepository->has($command->paymentId)) {
            return;
        }

        // Create payment aggregate
        $payment = Payment::create(
            $this->clock->now(),
            $command->paymentId,
            $command->status,
            $command->amount,
            $command->description,
            $command->paymentAppliedToId,
            $command->senderEmail,
            $command->senderName,
            $command->senderNationalIdCode,
            $command->effectiveDate,
        );

        try {
            $this->paymentRepository->save($payment);
        } catch (AggregateAlreadyExistsException $e) {
            // Idempotency: Another process created the payment concurrently
            return;
        }
    }
}
