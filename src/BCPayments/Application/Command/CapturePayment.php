<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Command;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCredentialValue;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;

class CapturePayment implements CommandInterface
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly PaymentCredentialValue $credentialValue,
    ) {
    }
}
