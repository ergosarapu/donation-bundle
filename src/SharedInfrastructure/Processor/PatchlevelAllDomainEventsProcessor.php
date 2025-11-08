<?php

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Processor;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use Patchlevel\EventSourcing\Attribute\Processor;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;

#[Processor('all_events')]
class PatchlevelAllDomainEventsProcessor
{
    public function __construct(private readonly EventBusInterface $eventBus)
    {
    }

    #[Subscribe('*')]
    public function onMessage(Message $message): void
    {
        $this->eventBus->dispatch($message->event());
    }
}
