<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\AcceptPaymentImport;
use ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\AcceptPaymentImportHandler;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class AcceptPaymentImportHandlerTest extends TestCase
{
    private Payment&MockObject $payment;
    private AcceptPaymentImportHandler $handler;
    private PaymentRepositoryInterface&MockObject $paymentRepository;
    private DateTimeImmutable $now;
    private AcceptPaymentImport $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = $this->createMock(Payment::class);
        $this->paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2024-02-01 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new AcceptPaymentImportHandler(
            $this->paymentRepository,
            $clock
        );

        $paymentId = PaymentId::generate();
        $this->command = new AcceptPaymentImport($paymentId);
    }

    public function testAcceptsPaymentImport(): void
    {
        $this->paymentRepository->expects($this->once())
            ->method('load')
            ->with($this->command->paymentId)
            ->willReturn($this->payment);
        $this->payment->expects($this->once())
            ->method('acceptImport')
            ->with($this->now);
        $this->paymentRepository->expects($this->once())
            ->method('save')
            ->with($this->payment);

        ($this->handler)($this->command);
    }
}
