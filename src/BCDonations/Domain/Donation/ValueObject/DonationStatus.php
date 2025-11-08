<?php

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject;

enum DonationStatus: String
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Failed = 'failed';
    case Refunded = 'refunded';
}
