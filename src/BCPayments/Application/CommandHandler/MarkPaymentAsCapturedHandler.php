<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsCaptured;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class MarkPaymentAsCapturedHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(MarkPaymentAsCaptured $command): void
    {
        $payment = $this->paymentRepository->load($command->paymentId);
        $payment->markCaptured($this->clock->now(), $command->capturedAmount);
        $this->paymentRepository->save($payment);
    }
}
