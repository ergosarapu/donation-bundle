<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringPlanRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlan;
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
        $recurringPlan = RecurringPlan::initiate(
            $this->clock->now(),
            $command->recurringPlanId,
            DonationId::generate(),
            $command->campaignId,
            $command->amount,
            $command->interval,
            $command->donorEmail,
            $command->gateway,
            $command->donorName,
            $command->donorNationalIdCode,
        );
        try {
            $this->recurringPlanRepository->save($recurringPlan);
        } catch (AggregateAlreadyExistsException $e) {
            // Idempotency: recurring donation already exists, do nothing
            return;
        }
    }
}
