<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\CompleteRecurringDonationAttempt;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringPlanRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class CompleteRecurringDonationAttemptHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly RecurringPlanRepositoryInterface $recurringPlanRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(CompleteRecurringDonationAttempt $command): void
    {
        $recurringPlan = $this->recurringPlanRepository->load($command->recurringPlanId);
        $recurringPlan->completeRecurringAttempt(
            $this->clock->now(),
            $command->donationId,
            $command->donationStatus,
            $command->recurringToken,
            $command->temporalFailure
        );
        $this->recurringPlanRepository->save($recurringPlan);
    }
}
