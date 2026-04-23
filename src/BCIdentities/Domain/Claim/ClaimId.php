<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class ClaimId implements AggregateRootId
{
    private const CLAIM_NAMESPACE = '6e8f9a2b-4c1d-47e9-b3f8-9d2e1f5c3a7b';

    private function __construct(
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

    public static function generate(ClaimSource $source): self
    {
        return self::fromString(Uuid::uuid5(self::CLAIM_NAMESPACE, $source->deterministicKey())->toString());
    }
}
