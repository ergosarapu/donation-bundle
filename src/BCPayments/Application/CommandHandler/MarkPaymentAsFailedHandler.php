<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsFailed;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class MarkPaymentAsFailedHandler implements CommandHandlerInterface
{
    public function __construct(private readonly PaymentRepositoryInterface $paymentRepository, private readonly ClockInterface $clock)
    {
    }

    public function __invoke(MarkPaymentAsFailed $command): void
    {
        $payment = $this->paymentRepository->load($command->paymentId);
        $payment->markFailed($this->clock->now(), $command->paymentMethodResult);
        $this->paymentRepository->save($payment);
    }
}
