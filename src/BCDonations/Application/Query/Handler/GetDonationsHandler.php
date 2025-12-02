<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetDonations;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port\DonationProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;

class GetDonationsHandler implements QueryHandlerInterface
{
    public function __construct(private readonly DonationProjectionRepositoryInterface $repository)
    {
    }

    /**
     * @return array<Donation>
     */
    public function __invoke(GetDonations $query): array
    {
        return $this->repository->findBy(recurringDonationId:$query->recurringDonationId);
    }
}
