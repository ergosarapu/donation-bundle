<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\UpdateCampaignPublicTitle;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

final class UpdateCampaignPublicTitleHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(UpdateCampaignPublicTitle $command): void
    {
        $campaign = $this->campaignRepository->load($command->campaignId);
        $campaign->updatePublicTitle($this->clock->now(), $command->publicTitle);
        $this->campaignRepository->save($campaign);
    }
}
