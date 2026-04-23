<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\CompleteRecurringDonationAttempt;
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
            $this->commandBus->dispatch(new CompleteRecurringDonationAttempt(
                $event->recurringPlanId,
                $event->donationId,
                $event->status,
            ));
        }
    }
}
