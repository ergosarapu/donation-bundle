<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateDonation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationRequest;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanInitiated;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class RecurringPlanInitiatedHandler implements EventHandlerInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(RecurringPlanInitiated $event): void
    {
        $donationRequest = new DonationRequest(
            $event->initialDonationId,
            $event->campaignId,
            $event->amount,
            $event->gateway,
            $event->donorIdentity,
            $event->description,
        );

        $this->commandBus->dispatch(new InitiateDonation(
            $donationRequest,
            $event->recurringPlanAction,
        ));
    }
}
