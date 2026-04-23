<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class DeduplicateId implements AggregateRootId
{
    private const NAMESPACE = '55df5f3c-7d5f-4f73-84a2-8d6f0fcd22d5';

    public function __construct(
        private readonly UuidInterface $id,
    ) {
    }

    public static function fromString(string $id): self
    {
        return new self(Uuid::fromString($id));
    }

    public function toString(): string
    {
        return $this->id->toString();
    }

    public static function generate(string $deduplicateKey): self
    {
        return new self(Uuid::uuid5(self::NAMESPACE, $deduplicateKey));
    }

}
