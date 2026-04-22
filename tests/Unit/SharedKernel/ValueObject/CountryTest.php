<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\SharedKernel\ValueObject;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Country;
use PHPUnit\Framework\TestCase;

final class CountryTest extends TestCase
{
    public function testNormalizesToUppercase(): void
    {
        $country = new Country(' ee ');

        self::assertSame('EE', $country->value);
    }

    public function testNamedConstructorForEstonia(): void
    {
        self::assertSame('EE', (new Country('EE'))->value);
    }

    public function testRejectsInvalidCountryCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Country('EST');
    }

    public function testEqualsWhenSame(): void
    {
        $a = new Country('EE');
        $b = new Country('ee');

        self::assertTrue($a->equals($b));
    }

    public function testEqualsWhenDifferent(): void
    {
        $a = new Country('EE');
        $b = new Country('US');

        self::assertFalse($a->equals($b));
    }

    public function testEqualsWhenOtherNull(): void
    {
        $a = new Country('EE');

        self::assertFalse($a->equals(null));
    }
}
