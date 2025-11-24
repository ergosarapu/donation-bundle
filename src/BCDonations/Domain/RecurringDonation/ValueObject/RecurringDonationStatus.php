<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject;

enum RecurringDonationStatus: String
{
    case Pending = 'pending'; // temporary status during initiation
    case Failed = 'failed'; // allowed transition to: none
    case Active = 'active'; // allowed transition to: failing, expired, canceled
    case Failing = 'failing'; // allowed transition to: failed, active, expired, canceled
    case Expired = 'expired'; // allowed transition to: none
    case Canceled = 'canceled'; // allowed transition to: none
}
