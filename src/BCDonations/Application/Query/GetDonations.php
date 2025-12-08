<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query;

use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\RecurringPlanId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\Query;

class GetDonations implements Query
{
    public function __construct(public readonly RecurringPlanId $recurringPlanId)
    {
    }
}
