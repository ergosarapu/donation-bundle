<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Port;

use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\Identity;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityId;

interface IdentityRepositoryInterface
{
    public function save(Identity $identity): void;

    public function load(IdentityId $identityId): Identity;

    public function has(IdentityId $identityId): bool;
}
