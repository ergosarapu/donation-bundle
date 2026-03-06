<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Port;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\Campaign;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;

interface CampaignRepositoryInterface
{
    public function save(Campaign $campaign): void;

    public function load(CampaignId $campaignId): Campaign;

    public function has(CampaignId $campaignId): bool;
}
