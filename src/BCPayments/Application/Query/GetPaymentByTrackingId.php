<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Query;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\Query;

class GetPaymentByTrackingId implements Query
{
    public function __construct(
        public readonly string $trackingId,
    ) {
    }
}
