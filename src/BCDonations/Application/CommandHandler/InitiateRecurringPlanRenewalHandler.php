<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateRecurringPlanRenewal;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringPlanRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class InitiateRecurringPlanRenewalHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly RecurringPlanRepositoryInterface $recurringPlanRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(InitiateRecurringPlanRenewal $command): void
    {
        $recurringPlan = $this->recurringPlanRepository->load($command->recurringPlanId);
        $recurringPlan->initiateRenewal($this->clock->now(), DonationId::generate());
        $this->recurringPlanRepository->save($recurringPlan);
    }
}
