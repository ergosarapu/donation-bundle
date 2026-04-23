<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class Iban
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $normalized = mb_strtoupper(preg_replace('/\s+/', '', $value) ?? '');
        if ($normalized === '') {
            throw new \InvalidArgumentException('IBAN cannot be empty.');
        }
        if (!mb_check_encoding($normalized, 'ASCII')) {
            throw new \InvalidArgumentException('IBAN must contain ASCII characters only.');
        }
        // IBANs are at most 34 characters
        if (strlen($normalized) > 34) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid IBAN.', $normalized));
        }
        $this->value = $normalized;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
