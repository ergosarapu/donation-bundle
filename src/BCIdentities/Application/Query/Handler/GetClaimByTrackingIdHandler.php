<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\GetClaim;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\GetClaimByTrackingId;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Query\Port\TrackingStatusProjectionRepositoryInterface;

class GetClaimByTrackingIdHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly TrackingStatusProjectionRepositoryInterface $trackingStatusRepository,
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    public function __invoke(GetClaimByTrackingId $query): mixed
    {
        $status = $this->trackingStatusRepository->find($query->trackingId);
        $claimId = $status?->getClaimId();
        if ($claimId === null) {
            return null;
        }
        return $this->queryBus->ask(new GetClaim(ClaimId::fromString($claimId)));
    }
}
