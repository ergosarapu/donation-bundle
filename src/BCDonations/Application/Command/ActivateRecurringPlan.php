<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Command;

use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;

final class ActivateRecurringPlan implements CommandInterface
{
    public function __construct(
        public readonly RecurringPlanId $recurringPlanId,
        public readonly string $paymentMethodId,
    ) {
    }
}
