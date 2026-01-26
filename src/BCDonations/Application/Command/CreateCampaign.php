<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Command;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignName;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignPublicTitle;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;

final class CreateCampaign implements CommandInterface
{
    public readonly CampaignId $campaignId;

    public function __construct(
        public readonly CampaignName $name,
        public readonly CampaignPublicTitle $publicTitle,
    ) {
        $this->campaignId = CampaignId::generate();
    }
}
