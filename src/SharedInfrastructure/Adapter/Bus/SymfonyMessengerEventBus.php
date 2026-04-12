<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Adapter\Bus;

use ErgoSarapu\DonationBundle\SharedApplication\Message\DelayedMessage;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\Stamp\MessageMetadataStamp;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Messenger\MessageBusInterface;

class SymfonyMessengerEventBus implements EventBusInterface
{
    public function __construct(private readonly MessageBusInterface $eventBus)
    {
    }

    public function dispatch(object $event): string
    {
        if ($event instanceof DelayedMessage) {
            throw new InvalidArgumentException('Delaying events not supported yet.');
        }

        $envelope = $this->eventBus->dispatch($event);

        $metadataStamp = $envelope->last(MessageMetadataStamp::class);
        if ($metadataStamp === null) {
            throw new RuntimeException('MessageMetadataStamp is missing from the envelope.');
        }

        return $metadataStamp->trackingId;
    }

}
