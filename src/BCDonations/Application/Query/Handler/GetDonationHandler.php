<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port\DonationProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;

class GetDonationHandler implements QueryHandlerInterface
{
    public function __construct(private readonly DonationProjectionRepositoryInterface $repository)
    {
    }

    public function __invoke(GetDonation $query): ?Donation
    {
        return $this->repository->findOneBy(id: $query->donationId);
    }
}
