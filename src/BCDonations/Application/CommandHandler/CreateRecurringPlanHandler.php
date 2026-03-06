<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\CreateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringPlanRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlan;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class CreateRecurringPlanHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly RecurringPlanRepositoryInterface $recurringPlanRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(CreateRecurringPlan $command): void
    {
        // Idempotency: Check if recurring plan already exists
        if ($this->recurringPlanRepository->has($command->recurringPlanId)) {
            return;
        }

        $recurringPlan = RecurringPlan::create(
            $this->clock->now(),
            $command->recurringPlanId,
            $command->status,
            $command->interval,
            $command->initialDonationId,
            $command->campaignId,
            $command->paymentMethodId,
            $command->amount,
            $command->gateway,
            $command->donorDetails,
            $command->nextRenewalTime,
            $command->description,
            $command->initiatedAt,
        );
        try {
            $this->recurringPlanRepository->save($recurringPlan);
        } catch (AggregateAlreadyExistsException $e) {
            // Idempotency: Another process created the recurring plan concurrently
            return;
        }
    }
}
