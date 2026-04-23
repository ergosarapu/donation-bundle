<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel;

use ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\Stamp\MessageMetadataStamp;
use Patchlevel\EventSourcing\Attribute\Processor;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Symfony\Component\Messenger\MessageBusInterface;

#[Processor('all_events')]
class PatchlevelAllDomainEventsProcessor
{
    public function __construct(
        private readonly MessageBusInterface $eventBus,
    ) {
    }

    #[Subscribe('*')]
    public function onMessage(Message $message): void
    {
        $stamps = [];
        if ($message->hasHeader(MessageMetadataStamp::class)) {
            $stamps[] = $message->header(MessageMetadataStamp::class);
        }

        $this->eventBus->dispatch($message->event(), $stamps);
    }
}
