<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Command;

use DateTimeImmutable;
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
        ?CampaignId $campaignId = null,
        public readonly ?DateTimeImmutable $createdAt = null,
    ) {
        if ($campaignId === null) {
            $campaignId = CampaignId::generate();
        }
        $this->campaignId = $campaignId;
    }
}
