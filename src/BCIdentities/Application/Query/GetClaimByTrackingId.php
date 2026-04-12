<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\Query;

class GetClaimByTrackingId implements Query
{
    public function __construct(
        public readonly string $trackingId,
    ) {
    }
}
