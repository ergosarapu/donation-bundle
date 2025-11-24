<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetRecurringDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\RecurringDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port\RecurringDonationProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;

class GetRecurringDonationHandler implements QueryHandlerInterface
{
    public function __construct(private readonly RecurringDonationProjectionRepositoryInterface $repository)
    {
    }

    public function __invoke(GetRecurringDonation $query): ?RecurringDonation
    {
        return $this->repository->findOne($query->recurringDonationId);
    }
}
