<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\SharedKernel\ValueObject;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use PHPUnit\Framework\TestCase;

class ShortDescriptionTest extends TestCase
{
    public function testValidDescription(): void
    {
        $desc = new ShortDescription('A donation for charity');
        $this->assertSame('A donation for charity', $desc->toString());
    }

    public function testTrimsWhitespace(): void
    {
        $desc = new ShortDescription('  donation  ');
        $this->assertSame('donation', $desc->toString());
    }

    public function testEmptyStringThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ShortDescription('');
    }

    public function testWhitespaceOnlyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ShortDescription('   ');
    }

    public function testExactly140CharsIsAllowed(): void
    {
        $desc = new ShortDescription(str_repeat('a', 140));
        $this->assertSame(140, strlen($desc->toString()));
    }

    public function test141CharsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ShortDescription(str_repeat('a', 141));
    }
}
