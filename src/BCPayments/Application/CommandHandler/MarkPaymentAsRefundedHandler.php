<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsRefunded;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;

class MarkPaymentAsRefundedHandler implements CommandHandlerInterface
{
    public function __construct(private readonly PaymentRepositoryInterface $paymentRepository)
    {
    }

    public function __invoke(MarkPaymentAsRefunded $command): void
    {
        $payment = $this->paymentRepository->load($command->paymentId);
        $payment->markRefunded($command->remainingAmount);
        $this->paymentRepository->save($payment);
    }
}
