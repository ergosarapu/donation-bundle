<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\SharedKernel\ValueObject;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use PHPUnit\Framework\TestCase;

class GatewayTest extends TestCase
{
    public function testValidGateway(): void
    {
        $gateway = new Gateway('stripe');
        $this->assertSame('stripe', $gateway->id());
    }

    public function testTrimsWhitespace(): void
    {
        $gateway = new Gateway('  stripe  ');
        $this->assertSame('stripe', $gateway->id());
    }

    public function testEmptyStringThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Gateway('');
    }

    public function testWhitespaceOnlyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Gateway('   ');
    }

    public function testExactly32CharsIsAllowed(): void
    {
        $gateway = new Gateway(str_repeat('a', 32));
        $this->assertSame(32, strlen($gateway->id()));
    }

    public function test33CharsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Gateway(str_repeat('a', 33));
    }

    public function testMultibyteThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Gateway('strïpe');
    }
}
