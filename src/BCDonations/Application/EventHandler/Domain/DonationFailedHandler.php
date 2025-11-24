<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\MarkRecurringDonationAsFailed;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\MarkRecurringDonationAsFailing;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\DonationFailed;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class DonationFailedHandler implements EventHandlerInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(DonationFailed $event): void
    {
        if ($event->recurringDonationId !== null) {
            if ($event->failsRecurring) {
                $this->commandBus->dispatch(new MarkRecurringDonationAsFailed($event->recurringDonationId));
            } else {
                $this->commandBus->dispatch(new MarkRecurringDonationAsFailing($event->recurringDonationId));
            }
        }
    }
}
