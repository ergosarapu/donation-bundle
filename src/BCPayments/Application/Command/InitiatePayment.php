<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Command;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentRequest;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;

final class InitiatePayment implements CommandInterface
{
    public function __construct(
        public readonly PaymentRequest $paymentRequest,
    ) {
    }
}
