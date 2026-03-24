<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class PersonName
{
    public function __construct(
        public readonly string $givenName,
        public readonly string $familyName
    ) {
    }

    public function equals(?self $other): bool
    {
        return $other !== null
            && $this->givenName === $other->givenName
            && $this->familyName === $other->familyName;
    }
}
