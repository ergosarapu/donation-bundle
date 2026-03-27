<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class PersonName
{
    public readonly string $givenName;
    public readonly string $familyName;

    public function __construct(string $givenName, string $familyName)
    {
        $givenName = mb_trim($givenName);
        $familyName = mb_trim($familyName);
        if ($givenName === '') {
            throw new \InvalidArgumentException('Given name cannot be empty.');
        }
        if ($familyName === '') {
            throw new \InvalidArgumentException('Family name cannot be empty.');
        }
        if (mb_strlen($givenName) > 50) {
            throw new \InvalidArgumentException(sprintf('Given name cannot exceed 50 characters, got %d.', strlen($givenName)));
        }
        if (mb_strlen($familyName) > 50) {
            throw new \InvalidArgumentException(sprintf('Family name cannot exceed 50 characters, got %d.', strlen($familyName)));
        }
        $this->givenName = $givenName;
        $this->familyName = $familyName;
    }

    public function equals(?self $other): bool
    {
        if ($other === null) {
            return false;
        }

        return $this->givenName === $other->givenName
            && $this->familyName === $other->familyName;
    }
}
