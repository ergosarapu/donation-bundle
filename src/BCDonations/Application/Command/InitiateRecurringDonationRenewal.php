<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Command;

use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;

final class InitiateRecurringDonationRenewal implements CommandInterface
{
    public function __construct(
        public readonly RecurringDonationId $recurringDonationId,
    ) {
    }
}
