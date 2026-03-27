<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\SharedKernel\ValueObject;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use PHPUnit\Framework\TestCase;

class NationalIdCodeTest extends TestCase
{
    public function testValidNationalIdCode(): void
    {
        $code = new NationalIdCode('38001085718');
        $this->assertSame('38001085718', $code->value);
    }

    public function testTrimsWhitespace(): void
    {
        $code = new NationalIdCode('  38001085718  ');
        $this->assertSame('38001085718', $code->value);
    }

    public function testEmptyStringThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NationalIdCode('');
    }

    public function testWhitespaceOnlyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NationalIdCode('   ');
    }

    public function testEqualsWhenSame(): void
    {
        $a = new NationalIdCode('38001085718');
        $b = new NationalIdCode('38001085718');
        $this->assertTrue($a->equals($b));
    }

    public function testEqualsWhenDifferent(): void
    {
        $a = new NationalIdCode('38001085718');
        $b = new NationalIdCode('48901085718');
        $this->assertFalse($a->equals($b));
    }

    public function testEqualsWithNull(): void
    {
        $a = new NationalIdCode('38001085718');
        $this->assertFalse($a->equals(null));
    }

    public function testExactly20CharsIsAllowed(): void
    {
        $code = new NationalIdCode(str_repeat('1', 20));
        $this->assertSame(20, strlen($code->value));
    }

    public function test21CharsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NationalIdCode(str_repeat('1', 21));
    }

    public function testMultibyteThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NationalIdCode('3800108571ä');
    }
}
