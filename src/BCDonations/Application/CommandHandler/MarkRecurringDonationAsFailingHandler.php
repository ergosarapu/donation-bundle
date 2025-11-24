<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\MarkRecurringDonationAsFailing;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringDonationRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;

class MarkRecurringDonationAsFailingHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly RecurringDonationRepositoryInterface $recurringDonationRepository
    ) {
    }

    public function __invoke(MarkRecurringDonationAsFailing $command): void
    {

        if (!$this->recurringDonationRepository->has($command->recurringDonationId)) {
            // TODO: log warning about missing donation?
            return;
        }

        $recurringDonation = $this->recurringDonationRepository->load($command->recurringDonationId);
        $recurringDonation->markFailing();
        $this->recurringDonationRepository->save($recurringDonation);
    }
}
