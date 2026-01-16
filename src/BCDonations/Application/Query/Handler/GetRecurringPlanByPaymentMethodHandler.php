<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetRecurringPlanByPaymentMethod;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port\RecurringPlanProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;

class GetRecurringPlanByPaymentMethodHandler implements QueryHandlerInterface
{
    public function __construct(private readonly RecurringPlanProjectionRepositoryInterface $repository)
    {
    }

    public function __invoke(GetRecurringPlanByPaymentMethod $query): ?RecurringPlan
    {
        return $this->repository->findOne(null, null, $query->paymentMethodId);
    }
}
