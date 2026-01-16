<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\GenerateRedirectCaptureUrl;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsFailed;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentGatewayInterface;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\URL;
use Psr\Clock\ClockInterface;

class GenerateRedirectCaptureURLHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly ClockInterface $clock,
        private readonly CommandBusInterface $commandBus,
    ) {
    }
    public function __invoke(GenerateRedirectCaptureUrl $command): void
    {
        $payment = $this->paymentRepository->load($command->paymentId);
        $paymentRequest = $payment->reserveGatewayCall($this->clock->now());
        if ($paymentRequest === null) {
            // Idempotency check: if already reserved, do not proceed further
            return;
        }
        $this->paymentRepository->save($payment);

        // Generate URL
        $url = $this->paymentGateway->createCaptureRedirectUrl(
            $paymentRequest,
            $command->requestPaymentMethod,
        );

        if ($url === null) {
            $this->commandBus->dispatch(new MarkPaymentAsFailed($command->paymentId, null));
            return;
        }

        $payment->setRedirectURL($this->clock->now(), $url);
        $this->paymentRepository->save($payment);
    }
}
