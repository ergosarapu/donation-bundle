<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\InitiateDonationHandler;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\DonationRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationRequest;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonorIdentity;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class InitiateDonationHandlerTest extends TestCase
{
    private InitiateDonationHandler $handler;
    private DonationRepositoryInterface&MockObject $donationRepository;
    private DateTimeImmutable $now;
    private InitiateDonation $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->donationRepository = $this->createMock(DonationRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2024-02-01 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new InitiateDonationHandler(
            $this->donationRepository,
            $clock
        );

        $donationRequest = new DonationRequest(
            DonationId::generate(),
            CampaignId::generate(),
            new Money(5000, new Currency('EUR')),
            new Gateway('montonio'),
            new DonorIdentity(new Email('donor@example.com'))
        );

        $this->command = new InitiateDonation($donationRequest);
    }

    public function testInitiatesDonation(): void
    {
        $this->donationRepository->expects($this->once())
            ->method('has')
            ->with($this->command->donationRequest->donationId)
            ->willReturn(false);
        $this->donationRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($donation) {
                return $donation instanceof Donation;
            }));

        ($this->handler)($this->command);
    }

    public function testIgnoresCommandWhenDonationAlreadyExists(): void
    {
        $this->donationRepository->expects($this->once())
            ->method('has')
            ->with($this->command->donationRequest->donationId)
            ->willReturn(true);
        $this->donationRepository->expects($this->never())
            ->method('save');

        ($this->handler)($this->command);
    }

    public function testHandlesAggregateAlreadyExistsException(): void
    {
        $this->donationRepository->expects($this->once())
            ->method('has')
            ->with($this->command->donationRequest->donationId)
            ->willReturn(false);
        $this->donationRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new AggregateAlreadyExistsException('Donation already exists'));

        // Should not throw exception - idempotency handling
        ($this->handler)($this->command);
    }
}
