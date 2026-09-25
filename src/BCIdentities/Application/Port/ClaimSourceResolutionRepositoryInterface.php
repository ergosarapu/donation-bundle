<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Port;

use ErgoSarapu\DonationBundle\BCIdentities\Domain\ClaimSourceResolution\ClaimSourceResolution;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\ClaimSourceResolution\ClaimSourceResolutionId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\RepositoryInterface;

/**
 * @extends RepositoryInterface<ClaimSourceResolution, ClaimSourceResolutionId>
 */
interface ClaimSourceResolutionRepositoryInterface extends RepositoryInterface
{
}
