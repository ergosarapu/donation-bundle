<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateRecurringDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringDonationRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\RecurringDonation;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class InitiateRecurringDonationHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly RecurringDonationRepositoryInterface $recurringDonationRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(InitiateRecurringDonation $command): void
    {
        $recurringDonation = RecurringDonation::initiate(
            $this->clock->now(),
            $command->recurringDonationId,
            DonationId::generate(),
            $command->campaignId,
            $command->amount,
            $command->interval,
            $command->donorEmail,
            $command->gateway,
            $command->donorName,
            $command->donorNationalIdCode,
        );
        try {
            $this->recurringDonationRepository->save($recurringDonation);
        } catch (AggregateAlreadyExistsException $e) {
            // Idempotency: recurring donation already exists, do nothing
            return;
        }
    }
}
