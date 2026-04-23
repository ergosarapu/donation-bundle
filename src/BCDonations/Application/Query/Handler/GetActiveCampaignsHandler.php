<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetActiveCampaigns;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Campaign;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port\CampaignProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;

class GetActiveCampaignsHandler implements QueryHandlerInterface
{
    public function __construct(private readonly CampaignProjectionRepositoryInterface $repository)
    {
    }

    /**
     * @return array<Campaign>
     */
    public function __invoke(GetActiveCampaigns $query): array
    {
        return $this->repository->findBy(status: CampaignStatus::Active);
    }
}
