<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\SharedKernel\ValueObject;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function testValidEmail(): void
    {
        $email = new Email('user@example.com');
        $this->assertSame('user@example.com', $email->toString());
    }

    public function testTrimsWhitespace(): void
    {
        $email = new Email('  user@example.com  ');
        $this->assertSame('user@example.com', $email->toString());
    }

    public function testNormalizesToLowercase(): void
    {
        $email = new Email('User@Example.COM');
        $this->assertSame('user@example.com', $email->toString());
    }

    public function testEmptyStringThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Email('');
    }

    public function testWhitespaceOnlyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Email('   ');
    }

    public function testInvalidEmailThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Email('not-an-email');
    }

    public function testMissingDomainThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Email('user@');
    }

    public function testExactly254CharsIsAllowed(): void
    {
        // 64-char local + @ + three 61-char labels + .com = 254 chars total
        $email = new Email(str_repeat('a', 64) . '@' . str_repeat('a', 61) . '.' . str_repeat('a', 61) . '.' . str_repeat('a', 61) . '.com');
        $this->assertSame(254, strlen($email->toString()));
    }

    public function test255CharsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        // 64-char local + @ + 62 + 61 + 61 char labels + .com = 255 chars total
        new Email(str_repeat('a', 64) . '@' . str_repeat('a', 62) . '.' . str_repeat('a', 61) . '.' . str_repeat('a', 61) . '.com');
    }

    public function testEquals(): void
    {
        $a = new Email('user@example.com');
        $b = new Email('USER@EXAMPLE.COM');
        $c = new Email('other@example.com');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }
}
