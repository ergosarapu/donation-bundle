<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port\RecurringPlanProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;

class GetRecurringPlanHandler implements QueryHandlerInterface
{
    public function __construct(private readonly RecurringPlanProjectionRepositoryInterface $repository)
    {
    }

    public function __invoke(GetRecurringPlan $query): ?RecurringPlan
    {
        return $this->repository->findOne($query->recurringPlanId);
    }
}
