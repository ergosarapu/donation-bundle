<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ReActivateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\ReActivateRecurringPlanHandler;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringPlanRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class ReActivateRecurringPlanHandlerTest extends TestCase
{
    private ReActivateRecurringPlanHandler $handler;
    private RecurringPlanRepositoryInterface&MockObject $recurringPlanRepository;
    private ClockInterface&MockObject $clock;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recurringPlanRepository = $this->createMock(RecurringPlanRepositoryInterface::class);
        $this->clock = $this->createMock(ClockInterface::class);
        $this->now = new DateTimeImmutable('2024-02-01 12:00:00');

        $this->clock->method('now')->willReturn($this->now);

        $this->handler = new ReActivateRecurringPlanHandler(
            $this->recurringPlanRepository,
            $this->clock
        );
    }

    public function testReActivatesRecurringPlan(): void
    {
        $recurringPlanId = RecurringPlanId::generate();
        $command = new ReActivateRecurringPlan($recurringPlanId);

        $recurringPlan = $this->createMock(RecurringPlan::class);

        $this->recurringPlanRepository->expects($this->once())
            ->method('load')
            ->with($recurringPlanId)
            ->willReturn($recurringPlan);

        $recurringPlan->expects($this->once())
            ->method('reActivate')
            ->with($this->now);

        $this->recurringPlanRepository->expects($this->once())
            ->method('save')
            ->with($recurringPlan);

        ($this->handler)($command);
    }
}
