<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan;

enum RecurringPlanStatus: String
{
    case Pending = 'pending'; // allowed transition to: failed, active, canceled
    case Failed = 'failed'; // allowed transition to: none
    case Active = 'active'; // allowed transition to: failed, failing, expired, canceled
    case Failing = 'failing'; // allowed transition to: failed, active, expired, canceled
    case Expired = 'expired'; // allowed transition to: none
    case Canceled = 'canceled'; // allowed transition to: none
}
