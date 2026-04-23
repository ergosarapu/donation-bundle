<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsAuthorized;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class MarkPaymentAsAuthorizedHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(MarkPaymentAsAuthorized $command): void
    {
        $payment = $this->paymentRepository->load($command->paymentId);
        $payment->markAuthorized($this->clock->now(), $command->authorizedAmount, $command->paymentMethodResult);
        $this->paymentRepository->save($payment);
    }
}
