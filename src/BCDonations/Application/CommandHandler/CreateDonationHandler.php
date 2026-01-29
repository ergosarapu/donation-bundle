<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\CreateDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\DonationRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Donation;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class CreateDonationHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly DonationRepositoryInterface $donationRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(CreateDonation $command): void
    {
        // Idempotency: Check if donation already exists
        if ($this->donationRepository->has($command->donationId)) {
            return;
        }

        $donation = Donation::create(
            $this->clock->now(),
            $command->donationId,
            $command->amount,
            $command->campaignId,
            $command->paymentId,
            $command->description,
            $command->donorIdentity,
            $command->recurringPlanId,
            $command->createdAt,
        );

        try {
            $this->donationRepository->save($donation);
        } catch (AggregateAlreadyExistsException $e) {
            // Idempotency: Another process created the donation concurrently
            return;
        }
    }
}
