<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Command;

use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;

final class ResolveClaim implements CommandInterface
{
    public function __construct(
        public readonly ClaimId $claimId,
    ) {
    }
}
