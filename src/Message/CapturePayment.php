<?php

namespace ErgoSarapu\DonationBundle\Message;

class CapturePayment
{
    public function __construct(private int $paymentId)
    {
    }

    public function getPaymentId(): int {
        return $this->paymentId;
    }
}
