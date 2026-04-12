<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedApplication\Query\Handler;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Query\GetTrackingStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Query\Model\TrackingStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Query\Port\TrackingStatusProjectionRepositoryInterface;

class GetTrackingStatusHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly TrackingStatusProjectionRepositoryInterface $repository
    ) {
    }

    public function __invoke(GetTrackingStatus $query): ?TrackingStatus
    {
        return $this->repository->find($query->trackingId);
    }
}
