<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\MarkRecurringPlanAsFailed;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringPlanRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class MarkRecurringPlanAsFailedHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly RecurringPlanRepositoryInterface $recurringPlanRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(MarkRecurringPlanAsFailed $command): void
    {

        if (!$this->recurringPlanRepository->has($command->recurringPlanId)) {
            // TODO: log warning about missing donation?
            return;
        }

        $recurringPlan = $this->recurringPlanRepository->load($command->recurringPlanId);
        $recurringPlan->markFailed($this->clock->now());
        $this->recurringPlanRepository->save($recurringPlan);
    }
}
