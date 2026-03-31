<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;

final class DonationRequest
{
    public function __construct(
        public readonly DonationId $donationId,
        public readonly CampaignId $campaignId,
        public readonly Money $amount,
        public readonly Gateway $gateway,
        public readonly DonorDetails $donorDetails,
        public readonly ShortDescription $description,
    ) {
    }
}
