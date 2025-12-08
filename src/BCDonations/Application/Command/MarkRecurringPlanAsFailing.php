<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Command;

use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\RecurringPlanId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;

final class MarkRecurringPlanAsFailing implements CommandInterface
{
    public function __construct(
        public readonly RecurringPlanId $recurringPlanId,
    ) {
    }
}
