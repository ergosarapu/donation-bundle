<?php

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class NationalIdCode
{
    public function __construct(
        private readonly string $value,
    ) {
    }
}
