<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\SharedKernel\ValueObject;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function testValidMoney(): void
    {
        $money = new Money(1000, new Currency('EUR'));
        $this->assertSame(1000, $money->amount());
        $this->assertSame('EUR', $money->currency()->code());
    }

    public function testZeroAmountIsAllowed(): void
    {
        $money = new Money(0, new Currency('EUR'));
        $this->assertSame(0, $money->amount());
    }

    public function testEqualsWhenSame(): void
    {
        $a = new Money(1000, new Currency('EUR'));
        $b = new Money(1000, new Currency('EUR'));
        $this->assertTrue($a->equals($b));
    }

    public function testEqualsWhenDifferentAmount(): void
    {
        $a = new Money(1000, new Currency('EUR'));
        $b = new Money(2000, new Currency('EUR'));
        $this->assertFalse($a->equals($b));
    }

    public function testEqualsWhenDifferentCurrency(): void
    {
        $a = new Money(1000, new Currency('EUR'));
        $b = new Money(1000, new Currency('USD'));
        $this->assertFalse($a->equals($b));
    }

    public function testToString(): void
    {
        $money = new Money(1000, new Currency('EUR'));
        $this->assertSame('10 EUR', (string)$money);
    }
}
