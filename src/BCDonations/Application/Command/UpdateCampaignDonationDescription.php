<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Command;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;

final class UpdateCampaignDonationDescription implements CommandInterface
{
    public function __construct(
        public readonly CampaignId $campaignId,
        public readonly ShortDescription $donationDescription,
    ) {
    }
}
