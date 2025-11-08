<?php

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Command;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;

final class MarkDonationAsAccepted
{
    public function __construct(
        public readonly DonationId $donationId,
        public readonly Money $acceptedAmount,
    ) {
    }
}
