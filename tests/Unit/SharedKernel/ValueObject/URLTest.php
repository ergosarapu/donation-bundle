<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\SharedKernel\ValueObject;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\URL;
use PHPUnit\Framework\TestCase;

class URLTest extends TestCase
{
    public function testValidUrl(): void
    {
        $url = new URL('https://example.com/path');
        $this->assertSame('https://example.com/path', $url->value());
    }

    public function testTrimsWhitespace(): void
    {
        $url = new URL('  https://example.com  ');
        $this->assertSame('https://example.com', $url->value());
    }

    public function testEmptyStringThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new URL('');
    }

    public function testInvalidUrlThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new URL('not a url');
    }

    public function testMissingSchemeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new URL('example.com');
    }

    public function testExactly2048CharsIsAllowed(): void
    {
        $url = new URL('https://example.com/' . str_repeat('a', 2028));
        $this->assertSame(2048, strlen($url->value()));
    }

    public function test2049CharsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new URL('https://example.com/' . str_repeat('a', 2029));
    }
}
