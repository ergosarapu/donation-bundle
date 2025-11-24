<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ActivateRecurringDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\CompleteRecurringDonationRenewal;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\DonationAccepted;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class DonationAcceptedHandler implements EventHandlerInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(DonationAccepted $event): void
    {
        if ($event->recurringDonationId !== null) {
            if ($event->activatesRecurring) {
                $this->commandBus->dispatch(new ActivateRecurringDonation($event->recurringDonationId));
            } else {
                $this->commandBus->dispatch(new CompleteRecurringDonationRenewal($event->recurringDonationId));
            }
        }

    }
}
