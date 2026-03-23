<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Command;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\IdentityId;

final class CreateIdentity implements CommandInterface
{
    public function __construct(
        public readonly IdentityId $identityId,
    ) {
    }
}
