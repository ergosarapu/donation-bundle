<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Query;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\Query;

class GetInitiatedPayment implements Query
{
    public function __construct(
        public readonly PaymentId $paymentId,
    ) {
    }
}
