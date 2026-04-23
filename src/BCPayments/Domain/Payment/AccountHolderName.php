<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class AccountHolderName
{
    public readonly string $value;

    public function __construct(string $value)
    {
        /** @var string $value */
        $value = mb_trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('Account holder name cannot be empty.');
        }
        if (mb_strlen($value) > 70) {
            throw new \InvalidArgumentException(sprintf('Account holder name cannot exceed 70 characters, got %d.', strlen($value)));
        }
        $this->value = $value;
    }
}
