<?php

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class PersonName
{
    public function __construct(
        private readonly string $givenName,
        private readonly string $familyName
    ) {
    }
}
