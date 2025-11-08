<?php

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Command;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;

abstract class AbstractCreateDonation
{
    public DonationId $donationId;

    public function __construct(
        public readonly Money $amount,
        public readonly ?PersonName $donorName = null,
        public readonly ?Email $donorEmail = null,
        public readonly ?NationalIdCode $donorNationalIdCode = null,
    ) {
        $this->donationId = DonationId::generate();
    }
}
