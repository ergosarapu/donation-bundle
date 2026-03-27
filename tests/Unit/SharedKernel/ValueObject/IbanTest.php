<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\SharedKernel\ValueObject;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use PHPUnit\Framework\TestCase;

class IbanTest extends TestCase
{
    public function testValidIban(): void
    {
        $iban = new Iban('EE382200221020145685');
        $this->assertSame('EE382200221020145685', $iban->value);
    }

    public function testTrimsWhitespaceAndNormalizesToUppercase(): void
    {
        $iban = new Iban('  ee38 2200 2210 2014 5685  ');
        $this->assertSame('EE382200221020145685', $iban->value);
    }

    public function testStripsInternalSpaces(): void
    {
        $iban = new Iban('EE38 2200 2210 2014 5685');
        $this->assertSame('EE382200221020145685', $iban->value);
    }

    public function testEmptyStringThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Iban('');
    }

    public function testWhitespaceOnlyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Iban('   ');
    }

    public function testTooLongIbanThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        // 35 characters — exceeds the 34-char maximum (2+2+30)
        new Iban('EE3800000000000000000000000000000001');
    }

    public function testInvalidFormatIsAccepted(): void
    {
        // We don't do any format validation intentionally
        $iban = new Iban('NOTANIBAN');
        $this->assertSame('NOTANIBAN', $iban->value);
    }

    public function testEqualsWhenSame(): void
    {
        $a = new Iban('EE382200221020145685');
        $b = new Iban('EE38 2200 2210 2014 5685');
        $this->assertTrue($a->equals($b));
    }

    public function testEqualsWhenDifferent(): void
    {
        $a = new Iban('EE382200221020145685');
        $b = new Iban('GB29NWBK60161331926819');
        $this->assertFalse($a->equals($b));
    }

    public function testMultibyteThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Iban('EÉ38 2200 2210 2014 5685');
    }
}
