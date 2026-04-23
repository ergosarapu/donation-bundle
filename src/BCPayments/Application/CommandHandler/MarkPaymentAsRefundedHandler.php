<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsRefunded;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class MarkPaymentAsRefundedHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly ClockInterface $clock
    ) {
    }

    public function __invoke(MarkPaymentAsRefunded $command): void
    {
        $payment = $this->paymentRepository->load($command->paymentId);
        $payment->markRefunded($this->clock->now(), $command->remainingAmount);
        $this->paymentRepository->save($payment);
    }
}
