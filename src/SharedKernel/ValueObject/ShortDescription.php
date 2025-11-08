<?php

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class ShortDescription
{
    public function __construct(
        private readonly string $value,
    ) {
    }

    public function toString(): string
    {
        return $this->value;
    }
}
