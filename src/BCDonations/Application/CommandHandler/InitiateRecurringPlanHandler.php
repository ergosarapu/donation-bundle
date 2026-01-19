<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringPlanRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanAction;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class InitiateRecurringPlanHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly RecurringPlanRepositoryInterface $recurringPlanRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(InitiateRecurringPlan $command): void
    {
        // Idempotency: Check if recurring plan already initiated
        if ($this->recurringPlanRepository->has($command->recurringPlanId)) {
            return;
        }

        $recurringPlan = RecurringPlan::initiate(
            $this->clock->now(),
            RecurringPlanAction::forInit(),
            $command->donationRequest,
            $command->interval,
        );
        try {
            $this->recurringPlanRepository->save($recurringPlan);
        } catch (AggregateAlreadyExistsException $e) {
            // Idempotency: Another process created the recurring plan concurrently
            return;
        }
    }
}
