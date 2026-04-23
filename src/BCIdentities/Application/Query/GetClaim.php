<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query;

use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\Query;

class GetClaim implements Query
{
    public function __construct(
        public readonly ClaimId $claimId,
    ) {
    }
}
