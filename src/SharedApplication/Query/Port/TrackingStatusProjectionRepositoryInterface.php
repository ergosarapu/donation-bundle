<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedApplication\Query\Port;

use ErgoSarapu\DonationBundle\SharedApplication\Query\Model\TrackingStatus;

interface TrackingStatusProjectionRepositoryInterface
{
    public function find(string $trackingId): ?TrackingStatus;
}
