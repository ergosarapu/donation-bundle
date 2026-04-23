<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\SharedKernel\ValueObject;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\RawName;
use PHPUnit\Framework\TestCase;

class RawNameTest extends TestCase
{
    public function testValidRawName(): void
    {
        $name = new RawName('John Doe');
        $this->assertSame('John Doe', $name->toString());
    }

    public function testTrimsWhitespace(): void
    {
        $name = new RawName('  John Doe  ');
        $this->assertSame('John Doe', $name->toString());
    }

    public function testEmptyStringThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RawName('');
    }

    public function testWhitespaceOnlyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RawName('   ');
    }

    public function testEqualsWhenSame(): void
    {
        $a = new RawName('John Doe');
        $b = new RawName('John Doe');
        $this->assertTrue($a->equals($b));
    }

    public function testEqualsWhenDifferent(): void
    {
        $a = new RawName('John Doe');
        $b = new RawName('Jane Doe');
        $this->assertFalse($a->equals($b));
    }

    public function testExactly100CharsIsAllowed(): void
    {
        $name = new RawName(str_repeat('a', 100));
        $this->assertSame(100, strlen($name->toString()));
    }

    public function test101CharsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RawName(str_repeat('a', 101));
    }
}
