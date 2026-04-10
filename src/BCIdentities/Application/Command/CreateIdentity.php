<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Command;

use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;

final class CreateIdentity implements CommandInterface
{
    public function __construct(
        public readonly IdentityId $identityId,
    ) {
    }
}
