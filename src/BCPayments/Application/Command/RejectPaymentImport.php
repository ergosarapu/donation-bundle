<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Command;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;

class RejectPaymentImport implements CommandInterface
{
    public function __construct(
        public readonly PaymentId $paymentId,
    ) {
    }
}
