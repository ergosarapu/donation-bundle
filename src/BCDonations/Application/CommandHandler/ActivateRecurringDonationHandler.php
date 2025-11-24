<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ActivateRecurringDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringDonationRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class ActivateRecurringDonationHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly RecurringDonationRepositoryInterface $recurringDonationRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(ActivateRecurringDonation $command): void
    {
        $recurringDonation = $this->recurringDonationRepository->load($command->recurringDonationId);
        $recurringDonation->activate($this->clock);
        $this->recurringDonationRepository->save($recurringDonation);
    }
}
