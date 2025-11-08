<?php

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\MarkDonationAsAccepted;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\DonationRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;

class MarkDonationAsAcceptedHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly DonationRepositoryInterface $donationRepository
    ) {}

    public function __invoke(MarkDonationAsAccepted $command): void {

        $donationId = DonationId::fromString($command->donationId->toString());
        if (!$this->donationRepository->has($donationId)){
            // TODO: log warning about missing donation?
            return;
        }

        $donation = $this->donationRepository->load($donationId);
        $donation->markAccepted($command->acceptedAmount);
        $this->donationRepository->save($donation);
    }
}
