<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\FailDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\FailDonationHandler;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\DonationRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid\Uuid;

class FailDonationHandlerTest extends TestCase
{
    private Donation&MockObject $donation;
    private FailDonationHandler $handler;
    private DonationRepositoryInterface&MockObject $donationRepository;
    private DateTimeImmutable $now;
    private FailDonation $command;
    private string $paymentId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->donation = $this->createMock(Donation::class);
        $this->donationRepository = $this->createMock(DonationRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2024-02-01 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new FailDonationHandler(
            $this->donationRepository,
            $clock
        );

        $donationId = DonationId::generate();
        $this->paymentId = Uuid::uuid7()->toString();
        $this->command = new FailDonation($donationId, $this->paymentId);
    }

    public function testFailsDonation(): void
    {
        $this->donationRepository->expects($this->once())
            ->method('load')
            ->with($this->command->donationId)
            ->willReturn($this->donation);
        $this->donation->expects($this->once())
            ->method('fail')
            ->with($this->now, $this->paymentId);
        $this->donationRepository->expects($this->once())
            ->method('save')
            ->with($this->donation);

        ($this->handler)($this->command);
    }
}
