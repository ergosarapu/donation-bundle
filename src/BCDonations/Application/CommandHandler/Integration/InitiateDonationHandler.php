<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\Integration;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateRecurringPlan;
use ErgoSarapu\DonationBundle\IntegrationContracts\Donations\Command\InitiateDonationIntegrationCommand;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;

class InitiateDonationHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(InitiateDonationIntegrationCommand $command): void
    {
        if ($command->recurringInterval !== null) {
            $this->commandBus->dispatch(new InitiateRecurringPlan($command->recurringInterval, $command->donationRequest));
        } else {
            $this->commandBus->dispatch(new InitiateDonation($command->donationRequest));
        }
    }
}
