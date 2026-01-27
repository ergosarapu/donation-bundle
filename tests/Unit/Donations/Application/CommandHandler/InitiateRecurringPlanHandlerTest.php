<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\InitiateRecurringPlanHandler;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringPlanRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationRequest;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonorIdentity;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringInterval;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlan;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class InitiateRecurringPlanHandlerTest extends TestCase
{
    private InitiateRecurringPlanHandler $handler;
    private RecurringPlanRepositoryInterface&MockObject $recurringPlanRepository;
    private DateTimeImmutable $now;
    private InitiateRecurringPlan $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recurringPlanRepository = $this->createMock(RecurringPlanRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2024-02-01 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new InitiateRecurringPlanHandler(
            $this->recurringPlanRepository,
            $clock
        );

        $donationRequest = new DonationRequest(
            DonationId::generate(),
            CampaignId::generate(),
            new Money(5000, new Currency('EUR')),
            new Gateway('test-gateway'),
            new DonorIdentity(new Email('donor@example.com')),
            new ShortDescription('Test donation')
        );

        $interval = new RecurringInterval(RecurringInterval::Monthly);
        $this->command = new InitiateRecurringPlan($interval, $donationRequest);
    }

    public function testInitiatesRecurringPlan(): void
    {
        $this->recurringPlanRepository->expects($this->once())
            ->method('has')
            ->with($this->command->recurringPlanId)
            ->willReturn(false);
        $this->recurringPlanRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($recurringPlan) {
                return $recurringPlan instanceof RecurringPlan;
            }));

        ($this->handler)($this->command);
    }

    public function testIgnoresCommandWhenRecurringPlanAlreadyExists(): void
    {
        $this->recurringPlanRepository->expects($this->once())
            ->method('has')
            ->with($this->command->recurringPlanId)
            ->willReturn(true);
        $this->recurringPlanRepository->expects($this->never())
            ->method('save');

        ($this->handler)($this->command);
    }

    public function testHandlesAggregateAlreadyExistsException(): void
    {
        $this->recurringPlanRepository->expects($this->once())
            ->method('has')
            ->with($this->command->recurringPlanId)
            ->willReturn(false);
        $this->recurringPlanRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new AggregateAlreadyExistsException('Recurring plan already exists'));

        // Should not throw exception - idempotency handling
        ($this->handler)($this->command);
    }
}
