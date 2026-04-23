<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Port;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\Campaign;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\RepositoryInterface;

/**
 * @extends RepositoryInterface<Campaign, CampaignId>
 */
interface CampaignRepositoryInterface extends RepositoryInterface
{
}
