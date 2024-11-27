<?php

namespace ErgoSarapu\DonationBundle\MessageHandler;

use ErgoSarapu\DonationBundle\Message\CapturePayment;
use ErgoSarapu\DonationBundle\Repository\PaymentRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CapturePaymentHandler
{
    public function __construct(
        private PaymentRepository $paymentRepository,
    ) {
    }

    public function __invoke(CapturePayment $capturePayment): void
    {
        $payment = $this->paymentRepository->find($capturePayment->getPaymentId());

        // TODO: Implement payment capture
    }
}
