<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Campaign\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ActivateCampaign;
use ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\ActivateCampaignHandler;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\CampaignRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class ActivateCampaignHandlerTest extends TestCase
{
    private ActivateCampaignHandler $handler;
    private CampaignRepositoryInterface&MockObject $campaignRepository;
    private DateTimeImmutable $now;
    private ActivateCampaign $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->campaignRepository = $this->createMock(CampaignRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2026-01-26 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new ActivateCampaignHandler(
            $this->campaignRepository,
            $clock
        );

        $this->command = new ActivateCampaign(
            CampaignId::generate()
        );
    }

    public function testActivatesCampaign(): void
    {
        $this->campaignRepository->expects($this->once())
            ->method('load')
            ->with($this->command->campaignId);

        $this->campaignRepository->expects($this->once())
            ->method('save');

        ($this->handler)($this->command);
    }
}
