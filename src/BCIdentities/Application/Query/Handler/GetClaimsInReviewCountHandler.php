<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\GetClaimsInReviewCount;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Port\ClaimProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;

final class GetClaimsInReviewCountHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly ClaimProjectionRepositoryInterface $claimProjectionRepository,
    ) {
    }

    public function __invoke(GetClaimsInReviewCount $query): int
    {
        return $this->claimProjectionRepository->countInReview();
    }
}
