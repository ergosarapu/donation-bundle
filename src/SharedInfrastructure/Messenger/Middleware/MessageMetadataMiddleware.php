<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\Middleware;

use ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\MetadataContext;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\Stamp\MessageMetadataStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class MessageMetadataMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly MetadataContext $context
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        // Add metadata stamp if not already present
        $stamp = $envelope->last(MessageMetadataStamp::class);
        if (null === $stamp) {
            $stamp = $this->context->createStamp();
            $envelope = $envelope->with($stamp);
        }

        // Store previous context to restore after handling
        $previousMessageId = $this->context->getPreviousMessageId();
        $previousCorrelationId = $this->context->getCorrelationId();
        $previousTrackingId = $this->context->getTrackingId();

        // Update context with current message metadata
        $this->context->set(
            correlationId:$stamp->correlationId,
            previousMessageId: $stamp->messageId,
            trackingId: $stamp->trackingId,
        );

        // Handle the message
        $result = $stack->next()->handle($envelope, $stack);

        // Restore previous context
        $this->context->set(
            correlationId:$previousCorrelationId,
            previousMessageId:$previousMessageId,
            trackingId: $previousTrackingId,
        );

        return $result;
    }
}
