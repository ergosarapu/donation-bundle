<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Adapter;

use Doctrine\ORM\EntityManagerInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Query\Model\TrackingStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Query\Port\TrackingStatusProjectionRepositoryInterface;

class TrackingStatusProjectionRepository implements TrackingStatusProjectionRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $projectionEntityManager
    ) {
    }

    public function find(string $trackingId): ?TrackingStatus
    {
        return $this->projectionEntityManager->getRepository(TrackingStatus::class)->find($trackingId);
    }
}
