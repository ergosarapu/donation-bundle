<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Campaign\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ArchiveCampaign;
use ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\ArchiveCampaignHandler;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\CampaignRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\Campaign;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class ArchiveCampaignHandlerTest extends TestCase
{
    private ArchiveCampaignHandler $handler;
    private CampaignRepositoryInterface&MockObject $campaignRepository;
    private DateTimeImmutable $now;
    private ArchiveCampaign $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->campaignRepository = $this->createMock(CampaignRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2026-01-26 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new ArchiveCampaignHandler(
            $this->campaignRepository,
            $clock
        );

        $this->command = new ArchiveCampaign(
            CampaignId::generate()
        );
    }

    public function testArchivesCampaign(): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->expects($this->once())
            ->method('archive')
            ->with($this->now);

        $this->campaignRepository->expects($this->once())
            ->method('load')
            ->with($this->command->campaignId)
            ->willReturn($campaign);

        $this->campaignRepository->expects($this->once())
            ->method('save')
            ->with($campaign);

        ($this->handler)($this->command);
    }
}
