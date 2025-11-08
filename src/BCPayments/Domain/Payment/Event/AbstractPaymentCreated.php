<?php

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event;

use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;

abstract class AbstractPaymentCreated
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly Money $amount,
    ) {
    }

}
