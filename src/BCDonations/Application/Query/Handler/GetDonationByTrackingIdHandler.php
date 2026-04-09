<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetDonationByTrackingId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Query\Port\TrackingStatusProjectionRepositoryInterface;

class GetDonationByTrackingIdHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly TrackingStatusProjectionRepositoryInterface $trackingStatusRepository,
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    public function __invoke(GetDonationByTrackingId $query): mixed
    {
        $status = $this->trackingStatusRepository->find($query->trackingId);
        $donationId = $status?->getDonationId();
        if ($donationId === null) {
            return null;
        }
        return $this->queryBus->ask(new GetDonation(DonationId::fromString($donationId)));
    }
}
