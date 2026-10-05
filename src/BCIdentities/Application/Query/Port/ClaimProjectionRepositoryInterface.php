<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Port;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model\Claim;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimId;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSourceContext;

interface ClaimProjectionRepositoryInterface
{
    public function find(ClaimId $claimId): ?Claim;

    public function findBySource(ClaimSourceContext $sourceContext, string $sourceId): ?Claim;

}
