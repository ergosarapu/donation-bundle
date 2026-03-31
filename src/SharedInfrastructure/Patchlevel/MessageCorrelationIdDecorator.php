<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel;

use ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\CorrelationContext;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\Stamp\CorrelationIdStamp;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Repository\MessageDecorator\MessageDecorator;

final class MessageCorrelationIdDecorator implements MessageDecorator
{
    public function __construct(
        private readonly CorrelationContext $context
    ) {
    }

    public function __invoke(Message $message): Message
    {
        if ($this->context->correlationId === null) {
            $stamp = CorrelationIdStamp::generate();
        } else {
            $stamp = CorrelationIdStamp::fromString($this->context->correlationId);
        }

        return $message->withHeader($stamp);
    }
}
