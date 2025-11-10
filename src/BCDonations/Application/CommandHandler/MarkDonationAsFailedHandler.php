<?php

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\MarkDonationAsFailed;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\DonationRepositoryInterface;

class MarkDonationAsFailedHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly DonationRepositoryInterface $donationRepository
    ) {}

    public function __invoke(MarkDonationAsFailed $command): void {

        if (!$this->donationRepository->has($command->donationId)){
            // TODO: log warning about missing donation?
            return;
        }

        $donation = $this->donationRepository->load($command->donationId);
        $donation->markFailed();
        $this->donationRepository->save($donation);
    }
}
