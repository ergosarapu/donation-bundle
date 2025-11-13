<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsCanceled;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;

class MarkPaymentAsCanceledHandler implements CommandHandlerInterface
{
    public function __construct(private readonly PaymentRepositoryInterface $paymentRepository)
    {
    }

    public function __invoke(MarkPaymentAsCanceled $command): void
    {
        $payment = $this->paymentRepository->load($command->paymentId);
        $payment->markCanceled();
        $this->paymentRepository->save($payment);
    }
}
