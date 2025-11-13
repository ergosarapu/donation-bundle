<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Command;

use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;

class MarkPaymentAsAuthorized
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly Money $authorizedAmount
    ) {
    }
}
