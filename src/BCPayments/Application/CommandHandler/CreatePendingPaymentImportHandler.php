<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler;

use DateTimeZone;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\CreatePendingPaymentImport;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Port\PaymentProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class CreatePendingPaymentImportHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly PaymentProjectionRepositoryInterface $paymentProjectionRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(CreatePendingPaymentImport $command): ?PaymentId
    {

        // Lookup duplicate payment from projection
        $payment = $this->paymentProjectionRepository->findOne(
            bookingDate: $command->bookingDate->setTimezone(new DateTimeZone('UTC')), // Is UTC needed?
            sourceIdentifier: $command->sourceIdentifier,
            bankReference: $command->bankReference
        );
        if ($payment !== null) {
            return null;
        }

        // Duplicate payment not found from projection. In case it is due to projection not being up to date yet
        // we can abort the import based on deduplicate key check.
        $deduplicateKey =
            $command->bookingDate->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d') . '|' .
            $command->sourceIdentifier->value . '|' .
            $command->bankReference->value;

        // If the repository already has Payment with given deduplicate key, don't create new one
        if ($this->paymentRepository->getIdByDeduplicateKey($deduplicateKey) !== null) {
            return null;
        }

        $paymentId = PaymentId::generate();
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

        $this->paymentRepository->save($payment, $deduplicateKey);
        return $paymentId;
    }
}
