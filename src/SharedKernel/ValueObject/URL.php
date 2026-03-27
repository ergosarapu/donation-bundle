<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class URL
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $value = mb_trim($value);
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid URL.', $value));
        }
        if (mb_strlen($value) > 2048) {
            throw new \InvalidArgumentException(sprintf('URL cannot exceed 2048 characters, got %d.', strlen($value)));
        }
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }
}
