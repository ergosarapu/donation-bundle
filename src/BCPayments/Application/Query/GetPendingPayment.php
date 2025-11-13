<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Query;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\Query;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;

class GetPendingPayment implements Query
{
    public function __construct(
        public readonly PaymentId $paymentId,
    ) {
    }
}
