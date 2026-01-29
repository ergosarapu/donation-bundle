<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Campaign\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\CreateCampaign;
use ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\CreateCampaignHandler;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\Campaign;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignName;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignPublicTitle;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class CreateCampaignHandlerTest extends TestCase
{
    private CreateCampaignHandler $handler;
    private CampaignRepositoryInterface&MockObject $campaignRepository;
    private DateTimeImmutable $now;
    private CreateCampaign $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->campaignRepository = $this->createMock(CampaignRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2026-01-26 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new CreateCampaignHandler(
            $this->campaignRepository,
            $clock
        );

        $this->command = new CreateCampaign(
            new CampaignName('Test Campaign'),
            new CampaignPublicTitle('Test Public Title'),
            new ShortDescription('Test Donation Description')
        );
    }

    public function testCreatesCampaign(): void
    {
        $this->campaignRepository->expects($this->once())
            ->method('has')
            ->with($this->command->campaignId)
            ->willReturn(false);

        $this->campaignRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Campaign::class));

        ($this->handler)($this->command);
    }

    public function testIgnoresCommandWhenCampaignAlreadyExists(): void
    {
        $this->campaignRepository->expects($this->once())
            ->method('has')
            ->with($this->command->campaignId)
            ->willReturn(true);

        $this->campaignRepository->expects($this->never())
            ->method('save');

        ($this->handler)($this->command);
    }

    public function testHandlesAggregateAlreadyExistsException(): void
    {
        $this->campaignRepository->expects($this->once())
            ->method('has')
            ->willReturn(false);

        $this->campaignRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new AggregateAlreadyExistsException('Campaign already exists'));

        // Should not throw exception - idempotency handling
        ($this->handler)($this->command);
    }
}
