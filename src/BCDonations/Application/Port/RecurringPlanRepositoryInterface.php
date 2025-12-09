<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Port;

use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;

interface RecurringPlanRepositoryInterface
{
    public function save(RecurringPlan $recurringPlan): void;

    public function load(RecurringPlanId $recurringPlanId): RecurringPlan;

    public function has(RecurringPlanId $recurringPlanId): bool;
}
