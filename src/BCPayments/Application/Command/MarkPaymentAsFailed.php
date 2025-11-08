<?php

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Command;

use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;

class MarkPaymentAsFailed
{
    public function __construct(
        public readonly PaymentId $paymentId,
    ) {
    }
}
