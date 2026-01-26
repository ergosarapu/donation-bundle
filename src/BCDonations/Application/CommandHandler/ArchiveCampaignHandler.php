<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ArchiveCampaign;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

final class ArchiveCampaignHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(ArchiveCampaign $command): void
    {
        $campaign = $this->campaignRepository->load($command->campaignId);
        $campaign->archive($this->clock->now());
        $this->campaignRepository->save($campaign);
    }
}
