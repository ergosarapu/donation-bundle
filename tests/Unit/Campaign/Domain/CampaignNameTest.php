<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Campaign\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignName;
use PHPUnit\Framework\TestCase;

class CampaignNameTest extends TestCase
{
    public function testValidCampaignName(): void
    {
        $name = new CampaignName('Summer Campaign');
        $this->assertSame('Summer Campaign', $name->toString());
    }

    public function testTrimsWhitespace(): void
    {
        $name = new CampaignName('  Summer Campaign  ');
        $this->assertSame('Summer Campaign', $name->toString());
    }

    public function testEmptyStringThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CampaignName('');
    }

    public function testWhitespaceOnlyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CampaignName('   ');
    }

    public function testExactly64CharsIsAllowed(): void
    {
        $name = new CampaignName(str_repeat('a', 64));
        $this->assertSame(64, strlen($name->toString()));
    }

    public function test65CharsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CampaignName(str_repeat('a', 65));
    }
}
