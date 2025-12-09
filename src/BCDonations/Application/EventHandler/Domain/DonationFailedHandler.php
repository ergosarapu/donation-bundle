<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\MarkRecurringPlanAsFailed;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\MarkRecurringPlanAsFailing;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationFailed;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class DonationFailedHandler implements EventHandlerInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(DonationFailed $event): void
    {
        if ($event->recurringPlanId !== null) {
            if ($event->failsRecurring) {
                $this->commandBus->dispatch(new MarkRecurringPlanAsFailed($event->recurringPlanId));
            } else {
                $this->commandBus->dispatch(new MarkRecurringPlanAsFailing($event->recurringPlanId));
            }
        }
    }
}
