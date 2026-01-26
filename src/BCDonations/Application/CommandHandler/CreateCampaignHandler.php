<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\CreateCampaign;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\Campaign;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

final class CreateCampaignHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(CreateCampaign $command): void
    {
        // Idempotency check
        if ($this->campaignRepository->has($command->campaignId)) {
            return;
        }

        $campaign = Campaign::create(
            $this->clock->now(),
            $command->campaignId,
            $command->name,
            $command->publicTitle,
        );

        try {
            $this->campaignRepository->save($campaign);
        } catch (AggregateAlreadyExistsException) {
            // Concurrent creation handling
            return;
        }
    }
}
