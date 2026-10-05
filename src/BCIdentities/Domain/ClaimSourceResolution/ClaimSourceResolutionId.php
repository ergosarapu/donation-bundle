<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\ClaimSourceResolution;

use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSource;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSourceContext;
use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class ClaimSourceResolutionId implements AggregateRootId
{
    private const NAMESPACE = '7b10d329-8fb0-48d3-87d3-a773123a911d';

    public function __construct(
        private readonly UuidInterface $id,
    ) {
    }

    public static function fromString(string $id): self
    {
        return new self(Uuid::fromString($id));
    }

    public static function fromSource(ClaimSource $source): self
    {
        return self::generate($source->context, $source->id);
    }

    public static function generate(ClaimSourceContext $sourceContext, string $sourceId): self
    {
        return new self(Uuid::uuid5(self::NAMESPACE, $sourceContext->value . ':' . $sourceId));
    }

    public function toString(): string
    {
        return $this->id->toString();
    }
}
