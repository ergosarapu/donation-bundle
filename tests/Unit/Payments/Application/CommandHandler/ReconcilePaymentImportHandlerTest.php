<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\ReconcilePaymentImport;
use ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\ReconcilePaymentImportHandler;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class ReconcilePaymentImportHandlerTest extends TestCase
{
    private Payment&MockObject $importedPayment;
    private Payment&MockObject $existingPayment;
    private ReconcilePaymentImportHandler $handler;
    private PaymentRepositoryInterface&MockObject $paymentRepository;
    private DateTimeImmutable $now;
    private ReconcilePaymentImport $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importedPayment = $this->createMock(Payment::class);
        $this->existingPayment = $this->createMock(Payment::class);
        $this->paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2024-02-01 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new ReconcilePaymentImportHandler(
            $this->paymentRepository,
            $clock
        );

        $paymentId = PaymentId::generate();
        $reconcileWithPaymentId = PaymentId::generate();
        $this->command = new ReconcilePaymentImport($paymentId, $reconcileWithPaymentId);
    }

    public function testReconcilesPaymentImport(): void
    {
        $this->paymentRepository->expects($this->exactly(2))
            ->method('load')
            ->willReturnCallback(function (PaymentId $id) {
                if ($id === $this->command->paymentId) {
                    return $this->importedPayment;
                }
                if ($id === $this->command->reconcileWithPaymentId) {
                    return $this->existingPayment;
                }
            });
        $this->importedPayment->expects($this->once())
            ->method('reconcileImport')
            ->with($this->now, $this->existingPayment);
        $this->paymentRepository->expects($this->once())
            ->method('save')
            ->with($this->importedPayment);

        ($this->handler)($this->command);
    }
}
