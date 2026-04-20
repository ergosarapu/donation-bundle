<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Port;

use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\Claim;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\RepositoryInterface;

/**
 * @extends RepositoryInterface<Claim, ClaimId>
 */
interface ClaimRepositoryInterface extends RepositoryInterface
{
}
