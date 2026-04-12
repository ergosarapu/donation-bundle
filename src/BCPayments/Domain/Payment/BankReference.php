<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class BankReference
{
    public readonly string $value;

    public function __construct(string $value)
    {
        /** @var string $value */
        $value = mb_trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('Bank reference cannot be empty.');
        }
        if (!mb_check_encoding($value, 'ASCII')) {
            throw new \InvalidArgumentException('Bank reference must contain ASCII characters only.');
        }
        if (strlen($value) > 36) {
            throw new \InvalidArgumentException(sprintf('Bank reference cannot exceed 36 characters, got %d.', strlen($value)));
        }
        $this->value = $value;
    }
}
