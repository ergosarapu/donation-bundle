<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Campaign\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\UpdateCampaignName;
use ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\UpdateCampaignNameHandler;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignName;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class UpdateCampaignNameHandlerTest extends TestCase
{
    private UpdateCampaignNameHandler $handler;
    private CampaignRepositoryInterface&MockObject $campaignRepository;
    private DateTimeImmutable $now;
    private UpdateCampaignName $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->campaignRepository = $this->createMock(CampaignRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2026-01-26 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new UpdateCampaignNameHandler(
            $this->campaignRepository,
            $clock
        );

        $this->command = new UpdateCampaignName(
            CampaignId::generate(),
            new CampaignName('Updated Name')
        );
    }

    public function testUpdatesCampaignName(): void
    {
        $this->campaignRepository->expects($this->once())
            ->method('load')
            ->with($this->command->campaignId);

        $this->campaignRepository->expects($this->once())
            ->method('save');

        ($this->handler)($this->command);
    }
}
