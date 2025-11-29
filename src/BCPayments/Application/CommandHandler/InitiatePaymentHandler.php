<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentGatewayInterface;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command\InitiatePaymentIntegrationCommand;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class InitiatePaymentHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(InitiatePaymentIntegrationCommand $command): void
    {
        // Idempotency: Check if payment already initiated
        if ($this->paymentRepository->has($command->paymentId)) {
            return;
        }

        // Create payment gateway session (external side effect)
        // Note: This may create duplicate gateway sessions on retry if save fails
        // Consider using idempotency keys at gateway level for production
        $redirectUrl = $this->paymentGateway->createPaymentRedirectUrl(
            $command->gateway,
            $command->paymentId,
            $command->amount,
            $command->description,
            $command->email,
        );

        // Create new aggregate
        $payment = Payment::initiate(
            $this->clock->now(),
            $command->paymentId,
            $command->amount,
            $command->gateway,
            $command->description,
            $redirectUrl,
            $command->appliedTo,
        );
        try {
            $this->paymentRepository->save($payment);
        } catch (AggregateAlreadyExistsException $e) {
            // Race condition: Another process created it between has() check and save()
            // This is acceptable - payment exists, goal achieved
            return;
        }
    }
}
