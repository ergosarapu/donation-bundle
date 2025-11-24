<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\DonationRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Donation;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;

class InitiateDonationHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly DonationRepositoryInterface $donationRepository
    ) {
    }

    public function __invoke(InitiateDonation $command): void
    {
        $donation = Donation::initiate(
            $command->donationId,
            $command->campaignId,
            PaymentId::generate(),
            $command->amount,
            $command->gateway,
            $command->recurringActivation,
            $command->recurringDonationId,
            $command->donorName,
            $command->donorEmail,
            $command->donorNationalIdCode,
            $command->parentRecurringActivationDonationId,
        );
        try {
            $this->donationRepository->save($donation);
        } catch (AggregateAlreadyExistsException $e) {
            // Idempotency: donation already exists, do nothing
            return;
        }
    }
}
