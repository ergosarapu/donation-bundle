<?php

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\Query;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Donation;

/**
 * @implements Query<Donation>
 */
class GetPendingDonation implements Query
{
    public function __construct(public readonly DonationId $donationId)
    {
    }
}
