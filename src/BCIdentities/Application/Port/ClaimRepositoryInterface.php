<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Port;

use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\Claim;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ClaimId;

interface ClaimRepositoryInterface
{
    public function save(Claim $claim): void;

    public function load(ClaimId $claimId): Claim;

    public function has(ClaimId $claimId): bool;
}
