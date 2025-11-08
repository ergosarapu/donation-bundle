<?php

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use InvalidArgumentException;
use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class Money
{
    public function __construct(
        private readonly int $amount,
        private readonly Currency $currency
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency->equals($other->currency);
    }

    public function __toString(): string
    {
        return ($this->amount / 100) . ' ' . $this->currency;
    }
}
