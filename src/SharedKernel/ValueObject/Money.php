<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class Money
{
    public function __construct(
        private readonly int $amount,
        private readonly Currency $currency
    ) {
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
