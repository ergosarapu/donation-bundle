<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\MarkDonationAsFailed;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\DonationRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class MarkDonationAsFailedHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly DonationRepositoryInterface $donationRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(MarkDonationAsFailed $command): void
    {

        if (!$this->donationRepository->has($command->donationId)) {
            // TODO: log warning about missing donation?
            return;
        }

        $donation = $this->donationRepository->load($command->donationId);
        $donation->markFailed($this->clock->now(), $command->temporalFailure);
        $this->donationRepository->save($donation);
    }
}
