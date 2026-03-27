<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class ShortDescription
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $value = mb_trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('Short description cannot be empty.');
        }
        if (mb_strlen($value) > 140) {
            throw new \InvalidArgumentException(sprintf('Short description cannot exceed 140 characters, got %d.', strlen($value)));
        }
        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
