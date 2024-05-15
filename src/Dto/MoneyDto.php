<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Dto;

use Money\Currency;
use Money\Money;

class MoneyDto
{
    public static function fromMoney(Money $money): self
    {
        return new self($money->getAmount(), $money->getCurrency()->getCode());
    }

    public static function fromCurrency(string $currency): self
    {
        return new self('0', $currency);
    }

    public static function fromAmount(string $amount): self
    {
        return new self($amount, 'EUR');
    }

    public function __construct(
        public ?string $amount = null,
        public ?string $currency = null,
    ) {
    }

    public function toMoney(): Money
    {
        return new Money($this->amount, new Currency($this->currency));
    }
}
