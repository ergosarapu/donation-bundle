<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

class PaymentCredentialValue
{
    public function __construct(
        public readonly string $value,
    ) {
    }
}
