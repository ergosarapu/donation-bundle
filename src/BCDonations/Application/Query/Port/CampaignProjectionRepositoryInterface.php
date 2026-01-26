<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Campaign;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignStatus;

interface CampaignProjectionRepositoryInterface
{
    public function findOne(?CampaignId $id = null, ?CampaignStatus $status = null): ?Campaign;

    /**
     * @return array<Campaign>
     */
    public function findBy(?CampaignId $id = null, ?CampaignStatus $status = null): array;
}
