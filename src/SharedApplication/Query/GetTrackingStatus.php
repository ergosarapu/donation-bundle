<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedApplication\Query;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\Query;

class GetTrackingStatus implements Query
{
    public function __construct(
        public readonly string $trackingId,
    ) {
    }
}
