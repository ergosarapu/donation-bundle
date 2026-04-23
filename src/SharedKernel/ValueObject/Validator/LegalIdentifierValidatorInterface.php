<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Validator;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\LegalIdentifier;

interface LegalIdentifierValidatorInterface
{
    public function supports(LegalIdentifier $legalIdentifier): bool;

    public function isSatisfiedBy(string $value): bool;
}
