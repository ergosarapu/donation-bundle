<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Command;

use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSource;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;

final class InvalidateClaimSourceData implements CommandInterface
{
    public function __construct(
        public readonly ClaimSource $claimSource,
    ) {
    }
}
