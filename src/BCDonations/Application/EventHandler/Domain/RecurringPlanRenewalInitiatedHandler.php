<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateDonation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanRenewalInitiated;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class RecurringPlanRenewalInitiatedHandler implements EventHandlerInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(RecurringPlanRenewalInitiated $event): void
    {
        $this->commandBus->dispatch(new InitiateDonation(
            $event->renewalDonationId,
            $event->amount,
            $event->campaignId,
            $event->gateway,
            $event->recurringPlanId,
            $event->recurringToken,
            null, // donor name
            $event->donorEmail,
            null, // donor national ID code
        ));
    }
}
