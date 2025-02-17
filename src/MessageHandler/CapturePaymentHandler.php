<?php

namespace ErgoSarapu\DonationBundle\MessageHandler;

use ErgoSarapu\DonationBundle\Message\CapturePayment;
use ErgoSarapu\DonationBundle\Repository\PaymentRepository;
use Payum\Core\Payum;
use Payum\Core\Request\Capture;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CapturePaymentHandler
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private ?Payum $payum
        ) {
    }

    public function __invoke(CapturePayment $capturePayment): void
    {
        $payment = $this->paymentRepository->find($capturePayment->getPaymentId());
        $gateway = $this->payum->getGateway($payment->getGateway());
        $gateway->execute(new Capture($payment));
    }
}
