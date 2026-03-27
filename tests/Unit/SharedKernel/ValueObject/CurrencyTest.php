<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\SharedKernel\ValueObject;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use PHPUnit\Framework\TestCase;

class CurrencyTest extends TestCase
{
    public function testValidCurrencyCode(): void
    {
        $currency = new Currency('EUR');
        $this->assertSame('EUR', $currency->code());
    }

    public function testNormalizesToUppercase(): void
    {
        $currency = new Currency('eur');
        $this->assertSame('EUR', $currency->code());
    }

    public function testTrimsWhitespace(): void
    {
        $currency = new Currency('  USD  ');
        $this->assertSame('USD', $currency->code());
    }

    public function testEmptyStringThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Currency('');
    }

    public function testTwoLetterCodeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Currency('EU');
    }

    public function testFourLetterCodeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Currency('EURO');
    }

    public function testCodeWithDigitsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Currency('EU1');
    }

    public function testEqualsWhenSame(): void
    {
        $a = new Currency('EUR');
        $b = new Currency('eur');
        $this->assertTrue($a->equals($b));
    }

    public function testEqualsWhenDifferent(): void
    {
        $a = new Currency('EUR');
        $b = new Currency('USD');
        $this->assertFalse($a->equals($b));
    }

    public function testToString(): void
    {
        $currency = new Currency('EUR');
        $this->assertSame('EUR', (string)$currency);
    }
}
