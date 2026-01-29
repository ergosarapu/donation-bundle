<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\UpdateCampaignDonationDescription;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

final class UpdateCampaignDonationDescriptionHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(UpdateCampaignDonationDescription $command): void
    {
        $campaign = $this->campaignRepository->load($command->campaignId);
        $campaign->updateDonationDescription($this->clock->now(), $command->donationDescription);
        $this->campaignRepository->save($campaign);
    }
}
