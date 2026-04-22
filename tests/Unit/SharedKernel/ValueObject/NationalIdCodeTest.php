<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\SharedKernel\ValueObject;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Country;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use PHPUnit\Framework\TestCase;

class NationalIdCodeTest extends TestCase
{
    public function testValidNationalIdCode(): void
    {
        $code = new NationalIdCode('38001085718', new Country('EE'));
        $this->assertSame('38001085718', $code->value);
        $this->assertEquals(new Country('EE'), $code->country);
    }

    public function testValidNationalIdCodeUsingZeroFallbackChecksum(): void
    {
        $code = new NationalIdCode('10001010080', new Country('EE'));
        $this->assertSame('10001010080', $code->value);
        $this->assertEquals(new Country('EE'), $code->country);
    }

    public function testValidNationalIdCodeUsingSecondChecksumRound(): void
    {
        $code = new NationalIdCode('10001010214', new Country('EE'));
        $this->assertSame('10001010214', $code->value);
        $this->assertEquals(new Country('EE'), $code->country);
    }

    public function testTrimsWhitespace(): void
    {
        $code = new NationalIdCode('  38001085718  ', new Country('EE'));
        $this->assertSame('38001085718', $code->value);
    }

    public function testEmptyStringThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NationalIdCode('', new Country('EE'));
    }

    public function testWhitespaceOnlyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NationalIdCode('   ', new Country('EE'));
    }

    public function testMultibyteThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NationalIdCode('3800108571ä');
    }

    public function testCountryIsOptional(): void
    {
        $code = new NationalIdCode('12345678901');
        $this->assertSame('12345678901', $code->value);
        $this->assertNull($code->country);
    }

    public function testToStringIncludesCountryWhenPresent(): void
    {
        $code = new NationalIdCode('38001085718', new Country('EE'));
        $this->assertSame('EE 38001085718', (string) $code);
    }

    public function testToStringReturnsValueWhenCountryMissing(): void
    {
        $code = new NationalIdCode('not-validated');
        $this->assertSame('not-validated', (string) $code);
    }

    public function testEqualsWhenSame(): void
    {
        $a = new NationalIdCode('38001085718', new Country('EE'));
        $b = new NationalIdCode('38001085718', new Country('EE'));
        $this->assertTrue($a->equals($b));
    }

    public function testEqualsWhenDifferent(): void
    {
        $a = new NationalIdCode('38001085718', new Country('EE'));
        $b = new NationalIdCode('48901085713', new Country('EE'));
        $this->assertFalse($a->equals($b));
    }

    public function testEqualsWithNull(): void
    {
        $a = new NationalIdCode('38001085718', new Country('EE'));
        $this->assertFalse($a->equals(null));
    }

    public function testEqualsWhenBothCountriesMissing(): void
    {
        $a = new NationalIdCode('12345678901');
        $b = new NationalIdCode('12345678901');
        $this->assertTrue($a->equals($b));
    }

    public function testEqualsWhenFirstHasCountry(): void
    {
        $a = new NationalIdCode('38001085718', new Country('EE'));
        $b = new NationalIdCode('38001085718');
        $this->assertFalse($a->equals($b));
    }

    public function testEqualsWhenOtherHasCountry(): void
    {
        $a = new NationalIdCode('38001085718');
        $b = new NationalIdCode('38001085718', new Country('EE'));
        $this->assertFalse($a->equals($b));
    }

    public function testEqualsWhenCountryDiffers(): void
    {
        $a = new NationalIdCode('38001085718', new Country('EE'));
        $b = new NationalIdCode('38001085718', new Country('LV'));
        $this->assertFalse($a->equals($b));
    }

    public function testInvalidEstonianCodeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NationalIdCode('12345678901', new Country('EE'));
    }

    public function testInvalidEstonianCodeFormatThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NationalIdCode('02345678901', new Country('EE'));
    }

    public function testUnsupportedCountrySkipsValidation(): void
    {
        $code = new NationalIdCode('invalid value ?.:;', new Country('LV'));
        $this->assertSame('LV', $code->country?->value);
    }

    public function testHasSameValueAs(): void
    {
        $a = new NationalIdCode('38001085718', new Country('EE'));
        $b = new NationalIdCode('38001085718', new Country('EE'));
        $c = new NationalIdCode('38001085718');
        $d = new NationalIdCode('48901085713', new Country('EE'));

        $this->assertTrue($a->hasSameValueAs($b));
        $this->assertTrue($a->hasSameValueAs($c));
        $this->assertFalse($a->hasSameValueAs($d));
        $this->assertFalse($a->hasSameValueAs(null));
    }
}
