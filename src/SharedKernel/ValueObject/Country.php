<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class Country
{
    public readonly string $value;

    public function __construct(string $value)
    {
        /** @var string $value */
        $value = mb_trim($value);
        $normalized = mb_strtoupper($value);
        if (!preg_match('/^[A-Z]{2}$/', $normalized)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid ISO 3166-1 alpha-2 country code.', $normalized));
        }
        $this->value = $normalized;
    }

    public function equals(?self $other): bool
    {
        if ($other === null) {
            return false;
        }
        return $this->value === $other->value;
    }
}
