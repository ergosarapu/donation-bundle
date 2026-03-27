<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class RawName
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $value = mb_trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('Raw name cannot be empty.');
        }
        if (mb_strlen($value) > 100) {
            throw new \InvalidArgumentException(sprintf('Raw name cannot exceed 100 characters, got %d.', strlen($value)));
        }
        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
