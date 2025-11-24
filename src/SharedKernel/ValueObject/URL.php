<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class URL
{
    public function __construct(
        private readonly string $value,
    ) {
    }

    public function value(): string
    {
        return $this->value;
    }
}
