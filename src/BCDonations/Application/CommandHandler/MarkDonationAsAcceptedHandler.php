<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\MarkDonationAsAccepted;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\DonationRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;

class MarkDonationAsAcceptedHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly DonationRepositoryInterface $donationRepository
    ) {
    }

    public function __invoke(MarkDonationAsAccepted $command): void
    {

        if (!$this->donationRepository->has($command->donationId)) {
            // TODO: log warning about missing donation?
            return;
        }

        $donation = $this->donationRepository->load($command->donationId);
        $donation->markAccepted($command->acceptedAmount);
        $this->donationRepository->save($donation);
    }
}
