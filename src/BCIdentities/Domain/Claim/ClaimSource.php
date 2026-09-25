<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class ClaimSource
{
    private function __construct(
        public readonly ClaimSourceContext $context,
        public readonly string $id,
        public readonly ?string $type = null,
    ) {
    }

    public static function create(
        ClaimSourceContext $context,
        string $id,
        ?string $type = null,
    ): self
    {
        return new self($context, $id, $type);
    }
}
