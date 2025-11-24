<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class Gateway
{
    public function __construct(
        private readonly string $id,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }
}
