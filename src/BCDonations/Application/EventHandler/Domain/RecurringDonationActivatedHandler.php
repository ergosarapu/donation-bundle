<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateRecurringDonationRenewal;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationActivated;
use ErgoSarapu\DonationBundle\SharedApplication\Message\DelayedMessage;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class RecurringDonationActivatedHandler implements EventHandlerInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(RecurringDonationActivated $event): void
    {
        $this->commandBus->dispatch(
            new DelayedMessage(new InitiateRecurringDonationRenewal($event->id), $event->nextRenewalTime)
        );
    }
}
