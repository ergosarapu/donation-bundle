<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\CompleteRecurringDonationAttempt;
use ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\CompleteRecurringDonationAttemptHandler;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringPlanRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationStatus;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class CompleteRecurringDonationAttemptHandlerTest extends TestCase
{
    private RecurringPlan&MockObject $recurringPlan;
    private CompleteRecurringDonationAttemptHandler $handler;
    private RecurringPlanRepositoryInterface&MockObject $recurringPlanRepository;
    private DateTimeImmutable $now;
    private CompleteRecurringDonationAttempt $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recurringPlan = $this->createMock(RecurringPlan::class);
        $this->recurringPlanRepository = $this->createMock(RecurringPlanRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2024-02-01 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new CompleteRecurringDonationAttemptHandler(
            $this->recurringPlanRepository,
            $clock
        );

        $recurringPlanId = RecurringPlanId::generate();
        $donationId = DonationId::generate();
        $donationStatus = DonationStatus::Accepted;
        $this->command = new CompleteRecurringDonationAttempt($recurringPlanId, $donationId, $donationStatus);
    }

    public function testCompletesRecurringDonationAttempt(): void
    {
        $this->recurringPlanRepository->expects($this->once())
            ->method('load')
            ->with($this->command->recurringPlanId)
            ->willReturn($this->recurringPlan);
        $this->recurringPlan->expects($this->once())
            ->method('completeRecurringAttempt')
            ->with(
                $this->now,
                $this->command->donationId,
                $this->command->donationStatus
            );
        $this->recurringPlanRepository->expects($this->once())
            ->method('save')
            ->with($this->recurringPlan);

        ($this->handler)($this->command);
    }
}
