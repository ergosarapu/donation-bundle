<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetInitiatedDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port\DonationProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;

class GetInitiatedDonationHandler implements QueryHandlerInterface
{
    public function __construct(private readonly DonationProjectionRepositoryInterface $repository)
    {
    }

    public function __invoke(GetInitiatedDonation $query): ?Donation
    {
        return $this->repository->findOneBy($query->donationId, DonationStatus::Initiated);
    }
}
