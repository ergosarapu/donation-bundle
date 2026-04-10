<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\Integration;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ReActivateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\IntegrationContracts\Donations\Command\ReActivateRecurringPlanIntegrationCommand;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;

class ReActivateRecurringPlanHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(ReActivateRecurringPlanIntegrationCommand $command): void
    {
        $this->commandBus->dispatch(new ReActivateRecurringPlan(
            RecurringPlanId::fromString($command->recurringPlanId->toString()),
        ));
    }
}
