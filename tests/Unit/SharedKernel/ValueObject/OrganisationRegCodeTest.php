<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\SharedKernel\ValueObject;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\OrganisationRegCode;
use PHPUnit\Framework\TestCase;

class OrganisationRegCodeTest extends TestCase
{
    public function testValidRegCode(): void
    {
        $code = new OrganisationRegCode('80123456');
        $this->assertSame('80123456', $code->value);
    }

    public function testTrimsWhitespace(): void
    {
        $code = new OrganisationRegCode('  80123456  ');
        $this->assertSame('80123456', $code->value);
    }

    public function testEmptyStringThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new OrganisationRegCode('');
    }

    public function testWhitespaceOnlyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new OrganisationRegCode('   ');
    }

    public function testExactly20CharsIsAllowed(): void
    {
        $code = new OrganisationRegCode(str_repeat('1', 20));
        $this->assertSame(20, strlen($code->value));
    }

    public function test21CharsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new OrganisationRegCode(str_repeat('1', 21));
    }

    public function testMultibyteThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new OrganisationRegCode('8012345ü');
    }
}
