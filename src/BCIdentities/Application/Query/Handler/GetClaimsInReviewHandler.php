<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\GetClaimsInReview;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Port\ClaimProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;

final class GetClaimsInReviewHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly ClaimProjectionRepositoryInterface $claimProjectionRepository,
    ) {
    }

    public function __invoke(GetClaimsInReview $query): array
    {
        return $this->claimProjectionRepository->findInReview();
    }
}
