<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ActivateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringPlanRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class ActivateRecurringPlanHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly RecurringPlanRepositoryInterface $recurringPlanRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(ActivateRecurringPlan $command): void
    {
        $recurringPlan = $this->recurringPlanRepository->load($command->recurringPlanId);
        $recurringPlan->activate($this->clock->now(), $command->paymentMethodId);
        $this->recurringPlanRepository->save($recurringPlan);
    }
}
