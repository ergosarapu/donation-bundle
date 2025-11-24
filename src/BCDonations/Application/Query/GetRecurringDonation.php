<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query;

use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\Query;

class GetRecurringDonation implements Query
{
    public function __construct(public readonly RecurringDonationId $recurringDonationId)
    {
    }
}
