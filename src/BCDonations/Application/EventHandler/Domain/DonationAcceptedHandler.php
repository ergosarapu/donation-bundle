<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ActivateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\CompleteRecurringPlanRenewal;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\DonationAccepted;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class DonationAcceptedHandler implements EventHandlerInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(DonationAccepted $event): void
    {
        if ($event->recurringPlanId !== null) {
            if ($event->activatesRecurring) {
                $this->commandBus->dispatch(new ActivateRecurringPlan($event->recurringPlanId));
            } else {
                $this->commandBus->dispatch(new CompleteRecurringPlanRenewal($event->recurringPlanId));
            }
        }

    }
}
