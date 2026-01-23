<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ReActivateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringPlanRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class ReActivateRecurringPlanHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly RecurringPlanRepositoryInterface $recurringPlanRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(ReActivateRecurringPlan $command): void
    {
        $recurringPlan = $this->recurringPlanRepository->load($command->recurringPlanId);
        $recurringPlan->reActivate($this->clock->now());
        $this->recurringPlanRepository->save($recurringPlan);
    }
}
