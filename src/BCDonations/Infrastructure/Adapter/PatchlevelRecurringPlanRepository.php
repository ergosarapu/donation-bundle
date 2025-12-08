<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Infrastructure\Adapter;

use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringPlanRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Aggregate\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\RecurringPlanId;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Repository\PatchlevelRepositoryWrapperTrait;

final class PatchlevelRecurringPlanRepository implements RecurringPlanRepositoryInterface
{
    use PatchlevelRepositoryWrapperTrait;

    public function save(RecurringPlan $recurringPlan): void
    {
        $this->saveAggregate($recurringPlan);
    }

    public function load(RecurringPlanId $recurringPlanId): RecurringPlan
    {
        /** @var RecurringPlan $recurringPlan */
        $recurringPlan = $this->loadAggregate($recurringPlanId);
        return $recurringPlan;
    }

    public function has(RecurringPlanId $recurringPlanId): bool
    {
        return $this->hasAggregate($recurringPlanId);
    }
}
