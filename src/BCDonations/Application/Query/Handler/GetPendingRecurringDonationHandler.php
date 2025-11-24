<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetPendingRecurringDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\RecurringDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port\RecurringDonationProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;

class GetPendingRecurringDonationHandler implements QueryHandlerInterface
{
    public function __construct(private readonly RecurringDonationProjectionRepositoryInterface $repository)
    {
    }

    public function __invoke(GetPendingRecurringDonation $query): ?RecurringDonation
    {
        return $this->repository->findOne($query->recurringDonationId, RecurringDonationStatus::Pending);
    }
}
