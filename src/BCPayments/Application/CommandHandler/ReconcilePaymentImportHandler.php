<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\ReconcilePaymentImport;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class ReconcilePaymentImportHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(ReconcilePaymentImport $command): void
    {
        $importedPayment = $this->paymentRepository->load($command->paymentId);
        $existingPayment = $this->paymentRepository->load($command->reconcileWithPaymentId);

        $importedPayment->reconcileImport($this->clock->now(), $existingPayment);

        $this->paymentRepository->save($importedPayment);
    }
}
