<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateRecurringPlanRenewal;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanActivated;
use ErgoSarapu\DonationBundle\SharedApplication\Message\DelayedMessage;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class RecurringPlanActivatedHandler implements EventHandlerInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(RecurringPlanActivated $event): void
    {
        $this->commandBus->dispatch(
            new DelayedMessage(new InitiateRecurringPlanRenewal($event->id), $event->nextRenewalTime)
        );
    }
}
