<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\AcceptDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\AcceptDonationHandler;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\DonationRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid\Uuid;

class AcceptDonationHandlerTest extends TestCase
{
    private Donation&MockObject $donation;
    private AcceptDonationHandler $handler;
    private DonationRepositoryInterface&MockObject $donationRepository;
    private DateTimeImmutable $now;
    private AcceptDonation $command;
    private string $paymentId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->donation = $this->createMock(Donation::class);
        $this->donationRepository = $this->createMock(DonationRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2024-02-01 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new AcceptDonationHandler(
            $this->donationRepository,
            $clock
        );

        $donationId = DonationId::generate();
        $amount = new Money(5000, new Currency('EUR'));
        $this->paymentId = Uuid::uuid7()->toString();
        $this->command = new AcceptDonation($donationId, $amount, $this->paymentId);
    }

    public function testAcceptsDonation(): void
    {
        $this->donationRepository->expects($this->once())
            ->method('load')
            ->with($this->command->donationId)
            ->willReturn($this->donation);
        $this->donation->expects($this->once())
            ->method('accept')
            ->with($this->now, $this->command->amount, $this->paymentId, null);
        $this->donationRepository->expects($this->once())
            ->method('save')
            ->with($this->donation);

        ($this->handler)($this->command);
    }
}
