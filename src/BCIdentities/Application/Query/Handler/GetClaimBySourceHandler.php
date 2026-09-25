<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\GetClaimBySource;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model\Claim;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Port\ClaimProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;

final class GetClaimBySourceHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly ClaimProjectionRepositoryInterface $claimProjectionRepository,
    ) {
    }

    public function __invoke(GetClaimBySource $query): ?Claim
    {
        return $this->claimProjectionRepository->findBySource($query->sourceContext, $query->sourceId);
    }
}
