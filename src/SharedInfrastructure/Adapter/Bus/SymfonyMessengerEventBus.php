<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Adapter\Bus;

use ErgoSarapu\DonationBundle\SharedApplication\Message\DelayedMessage;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use InvalidArgumentException;
use Symfony\Component\Messenger\MessageBusInterface;

class SymfonyMessengerEventBus implements EventBusInterface
{
    public function __construct(private readonly MessageBusInterface $eventBus)
    {
    }

    public function dispatch(object $event): void
    {
        if ($event instanceof DelayedMessage) {
            throw new InvalidArgumentException('Delaying events not supported yet.');
        }

        $this->eventBus->dispatch($event);
    }

}
