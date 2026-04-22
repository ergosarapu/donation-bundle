<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\SharedKernel\ValueObject;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Country;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\OrganisationRegCode;
use PHPUnit\Framework\TestCase;

class OrganisationRegCodeTest extends TestCase
{
    public function testValidRegCode(): void
    {
        $code = new OrganisationRegCode('80123455', new Country('EE'));
        $this->assertSame('80123455', $code->value);
        $this->assertEquals(new Country('EE'), $code->country);
    }

    public function testValidRegCodeUsingSecondChecksumRound(): void
    {
        $code = new OrganisationRegCode('10000062', new Country('EE'));
        $this->assertSame('10000062', $code->value);
        $this->assertEquals(new Country('EE'), $code->country);
    }

    public function testValidRegCodeUsingZeroFallbackChecksum(): void
    {
        $code = new OrganisationRegCode('10000640', new Country('EE'));
        $this->assertSame('10000640', $code->value);
        $this->assertEquals(new Country('EE'), $code->country);
    }

    public function testTrimsWhitespace(): void
    {
        $code = new OrganisationRegCode('  80123455  ', new Country('EE'));
        $this->assertSame('80123455', $code->value);
    }

    public function testEmptyStringThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new OrganisationRegCode('', new Country('EE'));
    }

    public function testWhitespaceOnlyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new OrganisationRegCode('   ', new Country('EE'));
    }

    public function testMultibyteThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new OrganisationRegCode('8012345ü', new Country('EE'));
    }

    public function testCountryIsOptional(): void
    {
        $code = new OrganisationRegCode('not-validated');
        $this->assertSame('not-validated', $code->value);
        $this->assertNull($code->country);
    }

    public function testToStringIncludesCountryWhenPresent(): void
    {
        $code = new OrganisationRegCode('80123455', new Country('EE'));
        $this->assertSame('EE 80123455', (string) $code);
    }

    public function testToStringReturnsValueWhenCountryMissing(): void
    {
        $code = new OrganisationRegCode('not-validated');
        $this->assertSame('not-validated', (string) $code);
    }

    public function testInvalidEstonianRegCodeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new OrganisationRegCode('1234567', new Country('EE'));
    }

    public function testInvalidEstonianRegCodeChecksumThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new OrganisationRegCode('80123456', new Country('EE'));
    }

    public function testInvalidEstonianRegCodePrefixThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new OrganisationRegCode('22345674', new Country('EE'));
    }

    public function testUnsupportedCountrySkipsValidation(): void
    {
        $code = new OrganisationRegCode('invalid value ?.:;', new Country('LV'));
        $this->assertSame('LV', $code->country?->value);
    }

    public function testEqualsWhenSame(): void
    {
        $a = new OrganisationRegCode('80123455', new Country('EE'));
        $b = new OrganisationRegCode('80123455', new Country('EE'));
        $this->assertTrue($a->equals($b));
    }

    public function testEqualsWhenDifferent(): void
    {
        $a = new OrganisationRegCode('80123455', new Country('EE'));
        $b = new OrganisationRegCode('90123456', new Country('EE'));
        $this->assertFalse($a->equals($b));
    }

    public function testEqualsWithNull(): void
    {
        $a = new OrganisationRegCode('80123455', new Country('EE'));
        $this->assertFalse($a->equals(null));
    }

    public function testEqualsWhenBothCountriesMissing(): void
    {
        $a = new OrganisationRegCode('80123455');
        $b = new OrganisationRegCode('80123455');
        $this->assertTrue($a->equals($b));
    }

    public function testEqualsWhenFirstHasCountry(): void
    {
        $a = new OrganisationRegCode('80123455', new Country('EE'));
        $b = new OrganisationRegCode('80123455');
        $this->assertFalse($a->equals($b));
    }

    public function testEqualsWhenOtherHasCountry(): void
    {
        $a = new OrganisationRegCode('80123455');
        $b = new OrganisationRegCode('80123455', new Country('EE'));
        $this->assertFalse($a->equals($b));
    }

    public function testEqualsWhenCountryDiffers(): void
    {
        $a = new OrganisationRegCode('80123455', new Country('EE'));
        $b = new OrganisationRegCode('80123455', new Country('LV'));
        $this->assertFalse($a->equals($b));
    }
    public function testInvalidEstonianCodeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new OrganisationRegCode('80123454', new Country('EE'));
    }

    public function testHasSameValueAs(): void
    {
        $a = new OrganisationRegCode('80123455', new Country('EE'));
        $b = new OrganisationRegCode('80123455', new Country('EE'));
        $c = new OrganisationRegCode('80123455');
        $d = new OrganisationRegCode('90123456', new Country('EE'));

        $this->assertTrue($a->hasSameValueAs($b));
        $this->assertTrue($a->hasSameValueAs($c));
        $this->assertFalse($a->hasSameValueAs($d));
        $this->assertFalse($a->hasSameValueAs(null));
    }
}
