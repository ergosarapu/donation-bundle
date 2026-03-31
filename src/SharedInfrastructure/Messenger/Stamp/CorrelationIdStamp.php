<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\Stamp;

use Patchlevel\EventSourcing\Attribute\Header;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Stamp\StampInterface;

#[Header('correlation_id')]
final readonly class CorrelationIdStamp implements StampInterface
{
    public function __construct(
        private readonly string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid7()->toString());
    }
}
