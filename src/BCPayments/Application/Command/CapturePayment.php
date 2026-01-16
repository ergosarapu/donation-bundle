<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Command;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCredentialValue;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;

class CapturePayment
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly PaymentCredentialValue $credentialValue,
    ) {
    }
}
