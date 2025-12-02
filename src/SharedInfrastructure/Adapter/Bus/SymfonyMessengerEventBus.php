<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Adapter\Bus;

use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\IntegrationEventInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

class SymfonyMessengerEventBus implements EventBusInterface
{
    public function __construct(private readonly MessageBusInterface $eventBus)
    {
    }

    public function dispatch(object $event): void
    {
        if ($event instanceof IntegrationEventInterface) {
            $this->eventBus->dispatch(
                $event,
                [new TransportNamesStamp('integration_event')]
            );
            return;
        }

        $this->eventBus->dispatch($event, [new TransportNamesStamp('event')]);
    }

}
