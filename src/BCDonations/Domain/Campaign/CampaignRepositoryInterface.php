<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign;

interface CampaignRepositoryInterface
{
    public function save(Campaign $campaign): void;

    public function load(CampaignId $campaignId): Campaign;

    public function has(CampaignId $campaignId): bool;
}
