<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel;

use ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\MessageContext;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\Stamp\MessageMetadataStamp;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Repository\MessageDecorator\MessageDecorator;

final class MessageMetadataDecorator implements MessageDecorator
{
    public function __construct(
        private readonly MessageContext $context
    ) {
    }

    public function __invoke(Message $message): Message
    {
        $previousMessageId = $this->context->getCurrentMessageId();
        $previousCorrelationId = $this->context->getCurrentCorrelationId();

        return $message->withHeader(new MessageMetadataStamp(
            causationId: $previousMessageId,
            correlationId: $previousCorrelationId
        ));
    }
}
