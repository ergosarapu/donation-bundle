<?php

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Command;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\ValueObject\CampaignId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;

final class InitiateDonation extends AbstractCreateDonation
{
    public function __construct(
        Money $amount,
        public readonly CampaignId $campaignId,
        public readonly Gateway $gateway,
        ?PersonName $donorName = null,
        ?Email $donorEmail = null,
        ?NationalIdCode $donorNationalIdCode = null,
    ) {
        parent::__construct(
            $amount,
            $donorName,
            $donorEmail,
            $donorNationalIdCode,
        );
    }
}
