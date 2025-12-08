<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetPendingRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port\RecurringPlanProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\RecurringPlanStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;

class GetPendingRecurringPlanHandler implements QueryHandlerInterface
{
    public function __construct(private readonly RecurringPlanProjectionRepositoryInterface $repository)
    {
    }

    public function __invoke(GetPendingRecurringPlan $query): ?RecurringPlan
    {
        return $this->repository->findOne($query->recurringPlanId, RecurringPlanStatus::Pending);
    }
}
