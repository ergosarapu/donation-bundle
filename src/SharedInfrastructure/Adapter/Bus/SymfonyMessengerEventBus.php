<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Adapter\Bus;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class SymfonyMessengerEventBus implements EventBusInterface
{
    public function __construct(private readonly MessageBusInterface $eventBus)
    {
    }

    public function dispatch(object $event): void
    {
        $this->eventBus->dispatch($event);
    }

}
