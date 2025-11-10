<?php

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Command;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;

final class MarkDonationAsFailed
{
    public function __construct(
        public readonly DonationId $donationId
    ) {
    }
}
