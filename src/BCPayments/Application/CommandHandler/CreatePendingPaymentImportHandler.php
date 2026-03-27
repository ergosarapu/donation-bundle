<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\CreatePendingPaymentImport;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use Psr\Clock\ClockInterface;

class CreatePendingPaymentImportHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(CreatePendingPaymentImport $command): ?PaymentId
    {
        $paymentId = PaymentId::generateDeterministic(
            $command->sourceIdentifier->value . '|' . $command->bankReference->value,
            $command->bookingDate->setTime(0, 0, 0, 0)->getTimestamp() * 1000
        );

        // Idempotency: Check if payment already exists
        if ($this->paymentRepository->has($paymentId)) {
            return null;
        }

        // Import payment aggregate
        $payment = Payment::createPendingImport(
            $this->clock->now(),
            $paymentId,
            $command->sourceIdentifier,
            $command->bankReference,
            $command->status,
            $command->amount,
            $command->description,
            $command->bookingDate,
            $command->accountHolderName,
            $command->nationalIdCode,
            $command->organizationRegCode,
            $command->reference,
            $command->iban,
            $command->bic,
        );

        try {
            $this->paymentRepository->save($payment);
        } catch (AggregateAlreadyExistsException $e) {
            return null;
        }
        return $paymentId;
    }
}
