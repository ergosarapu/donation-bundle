<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateRecurringDonationRenewal;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringDonationRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;

class InitiateRecurringDonationRenewalHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly RecurringDonationRepositoryInterface $recurringDonationRepository,
    ) {
    }

    public function __invoke(InitiateRecurringDonationRenewal $command): void
    {
        $recurringDonation = $this->recurringDonationRepository->load($command->recurringDonationId);
        $recurringDonation->initiateRenewal();
        $this->recurringDonationRepository->save($recurringDonation);
    }
}
