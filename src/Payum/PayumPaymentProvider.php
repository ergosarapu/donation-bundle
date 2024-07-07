<?php

namespace ErgoSarapu\DonationBundle\Payum;

use ErgoSarapu\DonationBundle\Entity\Payment;
use Payum\Core\Payum;

class PayumPaymentProvider
{
    public function __construct(private ?Payum $payum, private ?array $paymentsConfig)
    {
    }

    public function getPaymentsConfig(): ?array {
        return $this->paymentsConfig;
    }

    public function createPayment(): Payment {
        return $this->payum->getStorage(Payment::class)->create();
    }

    public function updatePayment(Payment $payment): void {
        $this->payum->getStorage(Payment::class)->update($payment);
    }

    public function createCaptureTargetUrl(string $gatewayName, Payment $payment, string $route): string {
        return $this->payum->getTokenFactory()->createCaptureToken(
            $gatewayName, 
            $payment,
            $route // the route to redirect after capture
        )->getTargetUrl();
    }
}
