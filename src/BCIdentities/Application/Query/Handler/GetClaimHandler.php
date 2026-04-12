<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\GetClaim;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model\Claim;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Port\ClaimProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;

class GetClaimHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly ClaimProjectionRepositoryInterface $claimProjectionRepository,
    ) {
    }

    public function __invoke(GetClaim $query): ?Claim
    {
        return $this->claimProjectionRepository->find($query->claimId);
    }
}
