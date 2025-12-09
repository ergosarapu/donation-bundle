<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query;

use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\Query;

class GetRecurringPlan implements Query
{
    public function __construct(public readonly RecurringPlanId $recurringPlanId)
    {
    }
}
