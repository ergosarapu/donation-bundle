<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\Middleware;

use ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\CorrelationContext;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\Stamp\CorrelationIdStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class CorrelationIdMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly CorrelationContext $context
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $correlationId = $this->context->correlationId;

        $stamp = $envelope->last(CorrelationIdStamp::class);
        if ($stamp === null) {
            if ($correlationId === null) {
                $stamp = CorrelationIdStamp::generate();
            } else {
                $stamp = CorrelationIdStamp::fromString($correlationId);
            }
            $envelope = $envelope->with($stamp);
        }

        $this->context->correlationId = $stamp->toString();

        return $stack->next()->handle($envelope, $stack);
    }
}
