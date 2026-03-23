<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Command;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ClaimSource;

final class ResolveClaim implements CommandInterface
{
    public function __construct(
        public readonly ClaimSource $source,
    ) {
    }
}
