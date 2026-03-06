<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\AcceptDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\DonationRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class AcceptDonationHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly DonationRepositoryInterface $donationRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(AcceptDonation $command): void
    {
        $donation = $this->donationRepository->load($command->donationId);
        $donation->accept($this->clock->now(), $command->amount, $command->acceptedAt);
        $this->donationRepository->save($donation);
    }
}
