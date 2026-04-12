<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Port;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model\Claim;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimId;

interface ClaimProjectionRepositoryInterface
{
    public function find(ClaimId $claimId): ?Claim;

    /**
     * @return list<Claim>
     */
    public function findInReview(): array;

    public function countInReview(): int;
}
