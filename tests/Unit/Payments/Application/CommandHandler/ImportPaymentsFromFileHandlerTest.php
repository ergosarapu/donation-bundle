<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\ImportPaymentsFromFile;
use ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\ImportPaymentsFromFileHandler;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentFileImportResult;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentImportDecoderInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImportPaymentsFromFileHandlerTest extends TestCase
{
    private ImportPaymentsFromFileHandler $handler;
    private PaymentImportDecoderInterface&MockObject $decoder;
    private CommandBusInterface&MockObject $commandBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decoder = $this->createMock(PaymentImportDecoderInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);

        $this->handler = new ImportPaymentsFromFileHandler(
            $this->decoder,
            $this->commandBus
        );
    }

    public function testImportsPaymentsFromFile(): void
    {
        $fileName = '/path/to/file.xml';
        $command = new ImportPaymentsFromFile($fileName);

        $importCommand1 = $this->createMock(CommandInterface::class);
        $importCommand2 = $this->createMock(CommandInterface::class);
        $importCommand3 = $this->createMock(CommandInterface::class);

        $this->decoder->expects($this->once())
            ->method('getCommands')
            ->with($fileName)
            ->willReturn([$importCommand1, $importCommand2, $importCommand3]);

        $paymentId1 = PaymentId::generate();
        $paymentId2 = PaymentId::generate();
        $paymentId3 = PaymentId::generate();

        $this->commandBus->expects($this->exactly(3))
            ->method('dispatch')
            ->willReturnOnConsecutiveCalls($paymentId1, $paymentId2, $paymentId3);

        $result = ($this->handler)($command);

        $this->assertInstanceOf(PaymentFileImportResult::class, $result);
        $this->assertCount(3, $result->pendingPaymentIds);
        $this->assertEquals([$paymentId1, $paymentId2, $paymentId3], $result->pendingPaymentIds);
        $this->assertEquals(0, $result->skippedCount);
    }

    public function testHandlesSkippedPayments(): void
    {
        $fileName = '/path/to/file.xml';
        $command = new ImportPaymentsFromFile($fileName);

        $importCommand1 = $this->createMock(CommandInterface::class);
        $importCommand2 = $this->createMock(CommandInterface::class);
        $importCommand3 = $this->createMock(CommandInterface::class);

        $this->decoder->expects($this->once())
            ->method('getCommands')
            ->with($fileName)
            ->willReturn([$importCommand1, $importCommand2, $importCommand3]);

        $paymentId1 = PaymentId::generate();
        // Second payment already exists (returns null)
        $paymentId3 = PaymentId::generate();

        $this->commandBus->expects($this->exactly(3))
            ->method('dispatch')
            ->willReturnOnConsecutiveCalls($paymentId1, null, $paymentId3);

        $result = ($this->handler)($command);

        $this->assertInstanceOf(PaymentFileImportResult::class, $result);
        $this->assertCount(2, $result->pendingPaymentIds);
        $this->assertEquals([$paymentId1, $paymentId3], $result->pendingPaymentIds);
        $this->assertEquals(1, $result->skippedCount);
    }

    public function testHandlesAllPaymentsSkipped(): void
    {
        $fileName = '/path/to/file.xml';
        $command = new ImportPaymentsFromFile($fileName);

        $importCommand1 = $this->createMock(CommandInterface::class);
        $importCommand2 = $this->createMock(CommandInterface::class);

        $this->decoder->expects($this->once())
            ->method('getCommands')
            ->with($fileName)
            ->willReturn([$importCommand1, $importCommand2]);

        $this->commandBus->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturn(null);

        $result = ($this->handler)($command);

        $this->assertInstanceOf(PaymentFileImportResult::class, $result);
        $this->assertCount(0, $result->pendingPaymentIds);
        $this->assertEquals(2, $result->skippedCount);
    }

    public function testHandlesEmptyFile(): void
    {
        $fileName = '/path/to/empty-file.xml';
        $command = new ImportPaymentsFromFile($fileName);

        $this->decoder->expects($this->once())
            ->method('getCommands')
            ->with($fileName)
            ->willReturn([]);

        $this->commandBus->expects($this->never())
            ->method('dispatch');

        $result = ($this->handler)($command);

        $this->assertInstanceOf(PaymentFileImportResult::class, $result);
        $this->assertCount(0, $result->pendingPaymentIds);
        $this->assertEquals(0, $result->skippedCount);
    }

}
