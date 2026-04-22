<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Validator;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Country;

interface CountryBasedValidatorInterface
{
    public function supports(Country $country): bool;

    public function isSatisfiedBy(string $value): bool;
}
