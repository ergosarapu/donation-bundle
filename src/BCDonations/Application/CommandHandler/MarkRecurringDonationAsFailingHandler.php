<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\MarkRecurringDonationAsFailing;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringDonationRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class MarkRecurringDonationAsFailingHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly RecurringDonationRepositoryInterface $recurringDonationRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(MarkRecurringDonationAsFailing $command): void
    {

        if (!$this->recurringDonationRepository->has($command->recurringDonationId)) {
            // TODO: log warning about missing donation?
            return;
        }

        $recurringDonation = $this->recurringDonationRepository->load($command->recurringDonationId);
        $recurringDonation->markFailing($this->clock->now());
        $this->recurringDonationRepository->save($recurringDonation);
    }
}
