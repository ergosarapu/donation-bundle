<?php

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Command;

use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;

class MarkPaymentAsRefunded
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly Money $remainingAmount,
    ) {
    }
}
