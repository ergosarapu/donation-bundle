<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\CreateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\CreateRecurringPlanHandler;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringPlanRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonorDetails;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringInterval;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ExternalEntityId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class CreateRecurringPlanHandlerTest extends TestCase
{
    private CreateRecurringPlanHandler $handler;
    private RecurringPlanRepositoryInterface&MockObject $recurringPlanRepository;
    private DateTimeImmutable $now;
    private CreateRecurringPlan $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recurringPlanRepository = $this->createMock(RecurringPlanRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2024-02-01 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new CreateRecurringPlanHandler(
            $this->recurringPlanRepository,
            $clock
        );

        $recurringPlanId = RecurringPlanId::generate();
        $initialDonationId = DonationId::generate();
        $campaignId = CampaignId::generate();
        $paymentMethodId = ExternalEntityId::generate();
        $interval = new RecurringInterval(RecurringInterval::Monthly);
        $amount = new Money(5000, new Currency('EUR'));
        $gateway = new Gateway('test-gateway');
        $description = new ShortDescription('Test recurring plan');
        $donorDetails = new DonorDetails(new Email('donor@example.com'));
        $nextRenewalTime = $this->now->add($interval->toDateInterval());

        $this->command = new CreateRecurringPlan(
            $recurringPlanId,
            RecurringPlanStatus::Initiated,
            $interval,
            $initialDonationId,
            $campaignId,
            $paymentMethodId,
            $amount,
            $gateway,
            $donorDetails,
            $description,
            $nextRenewalTime,
            $this->now
        );
    }

    public function testCreatesRecurringPlan(): void
    {
        $this->recurringPlanRepository->expects($this->once())
            ->method('has')
            ->with($this->command->recurringPlanId)
            ->willReturn(false);

        $this->recurringPlanRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(RecurringPlan::class));

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
            ->willReturn(false);

        $this->recurringPlanRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new AggregateAlreadyExistsException('Recurring plan already exists'));

        // Should not throw exception - idempotency handling
        ($this->handler)($this->command);
    }
}
