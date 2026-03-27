<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Campaign\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignPublicTitle;
use PHPUnit\Framework\TestCase;

class CampaignPublicTitleTest extends TestCase
{
    public function testValidTitle(): void
    {
        $title = new CampaignPublicTitle('Help Build a School');
        $this->assertSame('Help Build a School', $title->toString());
    }

    public function testTrimsWhitespace(): void
    {
        $title = new CampaignPublicTitle('  Help Build a School  ');
        $this->assertSame('Help Build a School', $title->toString());
    }

    public function testEmptyStringThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CampaignPublicTitle('');
    }

    public function testWhitespaceOnlyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CampaignPublicTitle('   ');
    }

    public function testExactly64CharsIsAllowed(): void
    {
        $title = new CampaignPublicTitle(str_repeat('a', 64));
        $this->assertSame(64, strlen($title->toString()));
    }

    public function test65CharsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CampaignPublicTitle(str_repeat('a', 65));
    }
}
