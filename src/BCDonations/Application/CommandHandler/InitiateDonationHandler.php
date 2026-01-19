<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\DonationRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Donation;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class InitiateDonationHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly DonationRepositoryInterface $donationRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(InitiateDonation $command): void
    {
        // Idempotency: Check if donation already initiated
        if ($this->donationRepository->has($command->donationRequest->donationId)) {
            return;
        }

        $donation = Donation::initiate(
            $this->clock->now(),
            $command->donationRequest,
            $command->recurringPlanAction,
        );
        try {
            $this->donationRepository->save($donation);
        } catch (AggregateAlreadyExistsException $e) {
            // Idempotency: Another process created the donation concurrently
            return;
        }
    }
}
