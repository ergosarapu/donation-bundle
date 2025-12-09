<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation;

enum DonationStatus: String
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Failed = 'failed';
    case Refunded = 'refunded';
}
