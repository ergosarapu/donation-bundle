<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Port;

use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\Identity;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\RepositoryInterface;

/**
 * @extends RepositoryInterface<Identity, IdentityId>
 */
interface IdentityRepositoryInterface extends RepositoryInterface
{
}
