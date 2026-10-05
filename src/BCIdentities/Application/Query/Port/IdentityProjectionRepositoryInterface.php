<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Port;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model\Identity;

interface IdentityProjectionRepositoryInterface
{
    public function find(string $identityId): ?Identity;
}
