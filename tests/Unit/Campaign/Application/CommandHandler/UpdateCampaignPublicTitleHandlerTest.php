<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Campaign\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\UpdateCampaignPublicTitle;
use ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\UpdateCampaignPublicTitleHandler;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\CampaignRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignPublicTitle;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class UpdateCampaignPublicTitleHandlerTest extends TestCase
{
    private UpdateCampaignPublicTitleHandler $handler;
    private CampaignRepositoryInterface&MockObject $campaignRepository;
    private DateTimeImmutable $now;
    private UpdateCampaignPublicTitle $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->campaignRepository = $this->createMock(CampaignRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2026-01-26 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new UpdateCampaignPublicTitleHandler(
            $this->campaignRepository,
            $clock
        );

        $this->command = new UpdateCampaignPublicTitle(
            CampaignId::generate(),
            new CampaignPublicTitle('Updated Public Title')
        );
    }

    public function testUpdatesCampaignPublicTitle(): void
    {
        $this->campaignRepository->expects($this->once())
            ->method('load')
            ->with($this->command->campaignId);

        $this->campaignRepository->expects($this->once())
            ->method('save');

        ($this->handler)($this->command);
    }
}
