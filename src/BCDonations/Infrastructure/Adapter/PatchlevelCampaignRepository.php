<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Infrastructure\Adapter;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\Campaign;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Repository\PatchlevelRepositoryWrapperTrait;

final class PatchlevelCampaignRepository implements CampaignRepositoryInterface
{
    use PatchlevelRepositoryWrapperTrait;

    public function save(Campaign $campaign): void
    {
        $this->saveAggregate($campaign);
    }

    public function load(CampaignId $campaignId): Campaign
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadAggregate($campaignId);
        return $campaign;
    }

    public function has(CampaignId $campaignId): bool
    {
        return $this->hasAggregate($campaignId);
    }
}
