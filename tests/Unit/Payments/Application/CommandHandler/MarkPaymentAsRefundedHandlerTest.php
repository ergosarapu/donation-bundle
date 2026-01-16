<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsRefunded;
use ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\MarkPaymentAsRefundedHandler;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class MarkPaymentAsRefundedHandlerTest extends TestCase
{
    private Payment&MockObject $payment;
    private MarkPaymentAsRefundedHandler $handler;
    private PaymentRepositoryInterface&MockObject $paymentRepository;
    private DateTimeImmutable $now;
    private MarkPaymentAsRefunded $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = $this->createMock(Payment::class);
        $this->paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2024-02-01 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new MarkPaymentAsRefundedHandler(
            $this->paymentRepository,
            $clock
        );

        $paymentId = PaymentId::generate();
        $remainingAmount = new Money(5000, new Currency('EUR'));
        $this->command = new MarkPaymentAsRefunded($paymentId, $remainingAmount);
    }

    public function testMarksPaymentAsRefunded(): void
    {
        $this->paymentRepository->expects($this->once())
            ->method('load')
            ->with($this->command->paymentId)
            ->willReturn($this->payment);
        $this->payment->expects($this->once())
            ->method('markRefunded')
            ->with($this->now, $this->command->remainingAmount);
        $this->paymentRepository->expects($this->once())
            ->method('save')
            ->with($this->payment);

        ($this->handler)($this->command);
    }
}
