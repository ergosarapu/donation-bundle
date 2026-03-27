<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\SharedKernel\ValueObject;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use PHPUnit\Framework\TestCase;

class PersonNameTest extends TestCase
{
    public function testValidPersonName(): void
    {
        $name = new PersonName('John', 'Doe');
        $this->assertSame('John', $name->givenName);
        $this->assertSame('Doe', $name->familyName);
    }

    public function testTrimsWhitespace(): void
    {
        $name = new PersonName('  John  ', '  Doe  ');
        $this->assertSame('John', $name->givenName);
        $this->assertSame('Doe', $name->familyName);
    }

    public function testEmptyGivenNameThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PersonName('', 'Doe');
    }

    public function testWhitespaceGivenNameThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PersonName('   ', 'Doe');
    }

    public function testEmptyFamilyNameThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PersonName('John', '');
    }

    public function testWhitespaceFamilyNameThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PersonName('John', '   ');
    }

    public function testEqualsWhenSame(): void
    {
        $a = new PersonName('John', 'Doe');
        $b = new PersonName('John', 'Doe');
        $this->assertTrue($a->equals($b));
    }

    public function testEqualsWhenDifferent(): void
    {
        $a = new PersonName('John', 'Doe');
        $b = new PersonName('Jane', 'Doe');
        $this->assertFalse($a->equals($b));
    }

    public function testEqualsWithNull(): void
    {
        $a = new PersonName('John', 'Doe');
        $this->assertFalse($a->equals(null));
    }

    public function testGivenNameExactly50CharsIsAllowed(): void
    {
        $name = new PersonName(str_repeat('a', 50), 'Doe');
        $this->assertSame(50, strlen($name->givenName));
    }

    public function testGivenName51CharsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PersonName(str_repeat('a', 51), 'Doe');
    }

    public function testFamilyNameExactly50CharsIsAllowed(): void
    {
        $name = new PersonName('John', str_repeat('a', 50));
        $this->assertSame(50, strlen($name->familyName));
    }

    public function testFamilyName51CharsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PersonName('John', str_repeat('a', 51));
    }
}
