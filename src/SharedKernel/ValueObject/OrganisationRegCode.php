<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class OrganisationRegCode
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $value = mb_trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('Organisation registration code cannot be empty.');
        }
        if (!mb_check_encoding($value, 'ASCII')) {
            throw new \InvalidArgumentException('Organisation registration code must contain ASCII characters only.');
        }
        if (strlen($value) > 20) {
            throw new \InvalidArgumentException(sprintf('Organisation registration code cannot exceed 20 characters, got %d.', strlen($value)));
        }
        $this->value = $value;
    }

    public function equals(?self $other): bool
    {
        return $other !== null && $this->value === $other->value;
    }
}
