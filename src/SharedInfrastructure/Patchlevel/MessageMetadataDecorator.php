<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel;

use ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\MetadataContext;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Repository\MessageDecorator\MessageDecorator;

final class MessageMetadataDecorator implements MessageDecorator
{
    public function __construct(
        private readonly MetadataContext $context
    ) {
    }

    public function __invoke(Message $message): Message
    {
        return $message->withHeader($this->context->createStamp());
    }
}
