<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\Middleware;

use ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\MessageContext;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\Stamp\MessageMetadataStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class MessageMetadataMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly MessageContext $context
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        // Store previous context to restore after handling
        $previousMessageId = $this->context->getCurrentMessageId();
        $previousCorrelationId = $this->context->getCurrentCorrelationId();

        // Add metadata stamp if not already present
        $metadataStamp = $envelope->last(MessageMetadataStamp::class);
        if (null === $metadataStamp) {
            $metadataStamp = new MessageMetadataStamp(
                causationId: $previousMessageId,
                correlationId: $previousCorrelationId
            );
            $envelope = $envelope->with($metadataStamp);
        }

        // Update context with current message metadata
        $this->context->setCurrentMessageId($metadataStamp->messageId);
        $this->context->setCurrentCorrelationId($metadataStamp->correlationId);

        // Handle the message
        $result = $stack->next()->handle($envelope, $stack);

        // Restore previous context
        $this->context->setCurrentMessageId($previousMessageId);
        $this->context->setCurrentCorrelationId($previousCorrelationId);

        return $result;
    }
}
