<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel;

use Patchlevel\EventSourcing\Aggregate\Uuid;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'deduplicate.created')]
final class DeduplicateCreated
{
    public function __construct(
        public readonly DeduplicateId $deduplicateId,
        public readonly Uuid $entityId,
        public readonly string $deduplicateKey,
    ) {
    }
}
