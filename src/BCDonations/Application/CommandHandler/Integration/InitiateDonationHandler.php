<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\Integration;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationRequest;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonorDetails;
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
        $donationRequest = new DonationRequest(
            donationId: DonationId::fromString($command->donationId),
            campaignId: CampaignId::fromString($command->campaignId),
            amount: $command->amount,
            gateway: $command->gateway,
            donorDetails: new DonorDetails(
                email: $command->donorEmail,
                name: $command->donorName,
                nationalIdCode: $command->donorNationalIdCode,
            ),
            description: $command->description,
        );

        if ($command->recurringInterval !== null) {
            $this->commandBus->dispatch(new InitiateRecurringPlan($command->recurringInterval, $donationRequest));
        } else {
            $this->commandBus->dispatch(new InitiateDonation($donationRequest));
        }
    }
}
