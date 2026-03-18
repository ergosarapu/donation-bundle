<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Infrastructure\Adapter;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\EntityClaimRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\EntityClaim\EntityClaim;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\EntityClaim\EntityClaimId;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel\PatchlevelRepositoryWrapperTrait;

final class PatchlevelEntityClaimRepository implements EntityClaimRepositoryInterface
{
    use PatchlevelRepositoryWrapperTrait;

    public function save(EntityClaim $entityClaim): void
    {
        $this->saveAggregate($entityClaim);
    }

    public function load(EntityClaimId $entityClaimId): EntityClaim
    {
        /** @var EntityClaim $entityClaim */
        $entityClaim = $this->loadAggregate($entityClaimId);
        return $entityClaim;
    }

    public function has(EntityClaimId $entityClaimId): bool
    {
        return $this->hasAggregate($entityClaimId);
    }
}
