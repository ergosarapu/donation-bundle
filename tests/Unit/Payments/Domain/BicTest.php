<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Domain;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Bic;
use PHPUnit\Framework\TestCase;

class BicTest extends TestCase
{
    public function testValidBic(): void
    {
        $bic = new Bic('EEUHEE2X');
        $this->assertSame('EEUHEE2X', $bic->value);
    }

    public function testNormalizesToUppercase(): void
    {
        $bic = new Bic('eeuhee2x');
        $this->assertSame('EEUHEE2X', $bic->value);
    }

    public function testTrimsWhitespace(): void
    {
        $bic = new Bic('  EEUHEE2X  ');
        $this->assertSame('EEUHEE2X', $bic->value);
    }

    public function testEmptyStringThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Bic('');
    }

    public function testWhitespaceOnlyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Bic('   ');
    }

    public function test11CharsIsAllowed(): void
    {
        $bic = new Bic('ABCDEFGHIJK');
        $this->assertSame(11, strlen($bic->value));
    }

    public function testTooLongThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Bic('ABCDEFGHIJKL');
    }
}
