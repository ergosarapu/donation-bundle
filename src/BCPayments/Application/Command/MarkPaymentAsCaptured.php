<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Command;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;

class MarkPaymentAsCaptured
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly Money $capturedAmount,
        public readonly ?PaymentMethodResult $paymentMethodResult,
    ) {
    }
}
