<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ActivateCampaign;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

final class ActivateCampaignHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(ActivateCampaign $command): void
    {
        $campaign = $this->campaignRepository->load($command->campaignId);
        $campaign->activate($this->clock->now());
        $this->campaignRepository->save($campaign);
    }
}
