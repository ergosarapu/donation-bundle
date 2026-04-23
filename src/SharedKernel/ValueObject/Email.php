<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class Email
{
    private readonly string $value;

    public function __construct(string $value)
    {
        /** @var string $trimmed */
        $trimmed = mb_trim($value);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('Email cannot be empty.');
        }
        $normalized = mb_strtolower($trimmed);
        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid email address.', $normalized));
        }
        $this->value = $normalized;
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
