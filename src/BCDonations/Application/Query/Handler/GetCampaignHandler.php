<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetCampaign;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Campaign;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port\CampaignProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;

class GetCampaignHandler implements QueryHandlerInterface
{
    public function __construct(private readonly CampaignProjectionRepositoryInterface $repository)
    {
    }

    public function __invoke(GetCampaign $query): ?Campaign
    {
        return $this->repository->findOne($query->campaignId);
    }
}
