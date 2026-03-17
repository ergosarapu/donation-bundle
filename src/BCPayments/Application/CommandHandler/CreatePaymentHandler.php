<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\CreatePayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use Psr\Clock\ClockInterface;

class CreatePaymentHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(CreatePayment $command): PaymentId
    {
        $paymentId = $command->paymentId;
        // Idempotency: Check if payment already exists
        if ($this->paymentRepository->has($paymentId)) {
            return $paymentId;
        }

        // Create payment aggregate
        $payment = Payment::create(
            $this->clock->now(),
            $paymentId,
            $command->status,
            $command->amount,
            $command->description,
            $command->gateway,
            $command->paymentAppliedToId,
            $command->email,
            $command->name,
            $command->nationalIdCode,
            $command->initiatedAt,
            $command->capturedAt,
            $command->gatewayReference,
            $command->bankReference,
            $command->paymentReference,
            $command->legacyPaymentNumber,
            $command->iban,
        );

        try {
            $this->paymentRepository->save($payment);
            return $paymentId;
        } catch (AggregateAlreadyExistsException $e) {
            // Idempotency: Another process created the payment concurrently
            return $paymentId;
        }
    }
}
