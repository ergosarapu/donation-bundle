<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsPending;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;

class MarkPaymentAsPendingHandler implements CommandHandlerInterface
{
    public function __construct(private readonly PaymentRepositoryInterface $paymentRepository)
    {
    }

    public function __invoke(MarkPaymentAsPending $command): void
    {
        $payment = $this->paymentRepository->load($command->paymentId);
        $payment->markPending();
        $this->paymentRepository->save($payment);
    }
}
