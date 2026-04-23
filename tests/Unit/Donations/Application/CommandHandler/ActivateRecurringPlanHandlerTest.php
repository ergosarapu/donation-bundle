<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ActivateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\ActivateRecurringPlanHandler;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringPlanRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid\Uuid;

class ActivateRecurringPlanHandlerTest extends TestCase
{
    private RecurringPlan&MockObject $recurringPlan;
    private ActivateRecurringPlanHandler $handler;
    private RecurringPlanRepositoryInterface&MockObject $recurringPlanRepository;
    private DateTimeImmutable $now;
    private ActivateRecurringPlan $command;
    private string $paymentMethodId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recurringPlan = $this->createMock(RecurringPlan::class);
        $this->recurringPlanRepository = $this->createMock(RecurringPlanRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2024-02-01 12:00:00');
        $this->paymentMethodId = Uuid::uuid7()->toString();

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new ActivateRecurringPlanHandler(
            $this->recurringPlanRepository,
            $clock
        );

        $recurringPlanId = RecurringPlanId::generate();
        $this->command = new ActivateRecurringPlan($recurringPlanId, $this->paymentMethodId);
    }

    public function testActivatesRecurringPlan(): void
    {
        $this->recurringPlanRepository->expects($this->once())
            ->method('load')
            ->with($this->command->recurringPlanId)
            ->willReturn($this->recurringPlan);
        $this->recurringPlan->expects($this->once())
            ->method('activate')
            ->with($this->now, $this->paymentMethodId);
        $this->recurringPlanRepository->expects($this->once())
            ->method('save')
            ->with($this->recurringPlan);

        ($this->handler)($this->command);
    }
}
