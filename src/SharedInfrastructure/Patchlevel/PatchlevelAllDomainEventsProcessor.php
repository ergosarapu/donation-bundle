<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\CorrelationContext;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\Stamp\CorrelationIdStamp;
use Patchlevel\EventSourcing\Attribute\Processor;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;

#[Processor('all_events')]
class PatchlevelAllDomainEventsProcessor
{
    public function __construct(
        private readonly EventBusInterface $eventBus,
        private readonly CorrelationContext $correlationContext,
    ) {
    }

    #[Subscribe('*')]
    public function onMessage(Message $message): void
    {
        if ($message->hasHeader(CorrelationIdStamp::class)) {
            $stamp = $message->header(CorrelationIdStamp::class);
            $this->correlationContext->correlationId = $stamp->toString();
        }

        $this->eventBus->dispatch($message->event());
    }
}
