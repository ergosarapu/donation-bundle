<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\CreateDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\CreateDonationHandler;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\DonationRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonorDetails;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class CreateDonationHandlerTest extends TestCase
{
    private CreateDonationHandler $handler;
    private DonationRepositoryInterface&MockObject $donationRepository;
    private DateTimeImmutable $now;
    private CreateDonation $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->donationRepository = $this->createMock(DonationRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2024-02-01 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new CreateDonationHandler(
            $this->donationRepository,
            $clock
        );

        $donationId = DonationId::generate();
        $campaignId = CampaignId::generate();
        $paymentId = PaymentId::generate();
        $recurringPlanId = RecurringPlanId::generate();
        $amount = new Money(5000, new Currency('EUR'));
        $description = new ShortDescription('Test donation');
        $donorDetails = new DonorDetails(new Email('donor@example.com'));

        $this->command = new CreateDonation(
            $donationId,
            $campaignId,
            $paymentId,
            $recurringPlanId,
            $amount,
            $description,
            $donorDetails
        );
    }

    public function testCreatesDonation(): void
    {
        $this->donationRepository->expects($this->once())
            ->method('has')
            ->with($this->command->donationId)
            ->willReturn(false);

        $this->donationRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Donation::class));

        ($this->handler)($this->command);
    }

    public function testIgnoresCommandWhenDonationAlreadyExists(): void
    {
        $this->donationRepository->expects($this->once())
            ->method('has')
            ->with($this->command->donationId)
            ->willReturn(true);

        $this->donationRepository->expects($this->never())
            ->method('save');

        ($this->handler)($this->command);
    }

    public function testHandlesAggregateAlreadyExistsException(): void
    {
        $this->donationRepository->expects($this->once())
            ->method('has')
            ->willReturn(false);

        $this->donationRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new AggregateAlreadyExistsException('Donation already exists'));

        // Should not throw exception - idempotency handling
        ($this->handler)($this->command);
    }
}
