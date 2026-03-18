<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Port;

use ErgoSarapu\DonationBundle\BCIdentities\Domain\EntityClaim\EntityClaim;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\EntityClaim\EntityClaimId;

interface EntityClaimRepositoryInterface
{
    public function save(EntityClaim $entityClaim): void;

    public function load(EntityClaimId $entityClaimId): EntityClaim;

    public function has(EntityClaimId $entityClaimId): bool;
}
