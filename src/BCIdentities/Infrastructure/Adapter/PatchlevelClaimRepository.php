<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Infrastructure\Adapter;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\ClaimRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\Claim;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimId;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel\PatchlevelRepositoryWrapperTrait;

final class PatchlevelClaimRepository implements ClaimRepositoryInterface
{
    use PatchlevelRepositoryWrapperTrait;

    public function save(Claim $claim): void
    {
        $this->saveAggregate($claim);
    }

    public function load(ClaimId $claimId): Claim
    {
        /** @var Claim $entityClaim */
        $entityClaim = $this->loadAggregate($claimId);
        return $entityClaim;
    }

    public function has(ClaimId $claimId): bool
    {
        return $this->hasAggregate($claimId);
    }
}
