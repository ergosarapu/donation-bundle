<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation;

enum DonationStatus: String
{
    case Created = 'created';
    case Initiated = 'initiated';
    case Accepted = 'accepted';
    case Failed = 'failed';
    // case Refunded = 'refunded';
}
