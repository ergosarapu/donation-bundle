<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\CompleteRecurringPlanRenewal;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringPlanRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class CompleteRecurringPlanRenewalHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly RecurringPlanRepositoryInterface $recurringPlanRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(CompleteRecurringPlanRenewal $command): void
    {
        $recurringPlan = $this->recurringPlanRepository->load($command->recurringPlanId);
        $recurringPlan->completeRenewal($this->clock->now());
        $this->recurringPlanRepository->save($recurringPlan);
    }
}
