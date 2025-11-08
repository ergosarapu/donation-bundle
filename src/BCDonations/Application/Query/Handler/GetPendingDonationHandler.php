<?php

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetPendingDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port\DonationProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationStatus;
class GetPendingDonationHandler implements QueryHandlerInterface
{
    public function __construct(private readonly DonationProjectionRepositoryInterface $repository)
    {
    }

    public function __invoke(GetPendingDonation $query): ?Donation
    {
        return $this->repository->findOne($query->donationId, DonationStatus::Pending);
    }
}
