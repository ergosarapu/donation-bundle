<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\Query;

class GetDonation implements Query
{
    public function __construct(public readonly DonationId $donationId)
    {
    }
}
