<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query;

use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSourceContext;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\Query;

final class GetClaimBySource implements Query
{
    public function __construct(
        public readonly ClaimSourceContext $sourceContext,
        public readonly string $sourceId,
    ) {
    }
}
