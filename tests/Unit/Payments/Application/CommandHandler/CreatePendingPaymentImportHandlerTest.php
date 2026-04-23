<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Application\CommandHandler;

use DateTimeImmutable;
use DateTimeZone;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\CreatePendingPaymentImport;
use ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\CreatePendingPaymentImportHandler;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment as PaymentView;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Port\PaymentProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\AccountHolderName;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\BankReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Bic;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportSourceIdentifier;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\LegalIdentifier;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class CreatePendingPaymentImportHandlerTest extends TestCase
{
    private CreatePendingPaymentImportHandler $handler;
    private PaymentRepositoryInterface&MockObject $paymentRepository;
    private PaymentProjectionRepositoryInterface&MockObject $paymentProjectionRepository;
    private DateTimeImmutable $now;
    private CreatePendingPaymentImport $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $this->paymentProjectionRepository = $this->createMock(PaymentProjectionRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2024-02-01 12:00:00', new DateTimeZone('UTC'));

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new CreatePendingPaymentImportHandler(
            $this->paymentRepository,
            $this->paymentProjectionRepository,
            $clock
        );

        $sourceIdentifier = new PaymentImportSourceIdentifier('source-123');
        $bankReference = new BankReference('ref-456');
        $amount = new Money(5000, new Currency('EUR'));
        $description = new ShortDescription('Test payment import');
        $bookingDate = new DateTimeImmutable('2024-02-02 12:01:15', new DateTimeZone('UTC'));
        $accountHolderName = new AccountHolderName('John Doe');
        $nationalIdNumber = LegalIdentifier::nationalIdNumber('12345678901');
        $referenceNumber = new PaymentReference('1234567890');
        $iban = new Iban('EE382200221020145685');
        $bic = new Bic('HABAEE2X');

        $this->command = new CreatePendingPaymentImport(
            $sourceIdentifier,
            $bankReference,
            PaymentStatus::Initiated,
            $amount,
            $description,
            $bookingDate,
            $accountHolderName,
            $nationalIdNumber,
            $referenceNumber,
            $iban,
            $bic,
        );
    }

    public function testCreatesPaymentImport(): void
    {
        $this->paymentProjectionRepository->expects($this->once())
            ->method('findOne')
            ->with(
                null,
                null,
                new DateTimeImmutable('2024-02-02 12:01:15', new DateTimeZone('UTC')),
                new PaymentImportSourceIdentifier('source-123'),
                new BankReference('ref-456'),
            )
            ->willReturn(null);

        $this->paymentRepository->expects($this->once())
            ->method('getIdByDeduplicateKey')
            ->with('2024-02-02|source-123|ref-456')
            ->willReturn(null);

        $this->paymentRepository->expects($this->once())
            ->method('save')
            ->with(
                $this->isInstanceOf(Payment::class),
                '2024-02-02|source-123|ref-456',
            );

        $result = ($this->handler)($this->command);

        $this->assertInstanceOf(PaymentId::class, $result);
    }

    public function testIgnoresCommandWhenPaymentAlreadyExists(): void
    {
        $existingPayment = new PaymentView();

        $this->paymentProjectionRepository->expects($this->once())
            ->method('findOne')
            ->willReturn($existingPayment);

        $this->paymentRepository->expects($this->never())
            ->method('getIdByDeduplicateKey');

        $this->paymentRepository->expects($this->never())
            ->method('save');

        $result = ($this->handler)($this->command);

        $this->assertNull($result);
    }

    public function testIgnoresCommandWhenPaymentAlreadyExistsByDeduplicateKey(): void
    {
        $this->paymentProjectionRepository->expects($this->once())
            ->method('findOne')
            ->willReturn(null);

        $this->paymentRepository->expects($this->once())
            ->method('getIdByDeduplicateKey')
            ->with('2024-02-02|source-123|ref-456')
            ->willReturn(PaymentId::generate());

        $this->paymentRepository->expects($this->never())
            ->method('save');

        $result = ($this->handler)($this->command);
        $this->assertNull($result);
    }

    public function testUsesUtcBookingDateForDuplicateLookupAndDeduplicateKey(): void
    {
        $command = new CreatePendingPaymentImport(
            new PaymentImportSourceIdentifier('source-123'),
            new BankReference('ref-456'),
            PaymentStatus::Initiated,
            new Money(5000, new Currency('EUR')),
            new ShortDescription('Test deterministic ID'),
            new DateTimeImmutable('2024-02-02 01:30:45', new DateTimeZone('+02:00')), // Previous UTC day
            new AccountHolderName('John Doe'),
            LegalIdentifier::nationalIdNumber('12345678901'),
            new PaymentReference('1234567890'),
            new Iban('EE382200221020145685'),
            new Bic('HABAEE2X'),
        );

        $this->paymentProjectionRepository->expects($this->once())
            ->method('findOne')
            ->with(
                null,
                null,
                new DateTimeImmutable('2024-02-01 23:30:45', new DateTimeZone('UTC')),
                new PaymentImportSourceIdentifier('source-123'),
                new BankReference('ref-456'),
            )
            ->willReturn(null);

        $this->paymentRepository->expects($this->once())
            ->method('getIdByDeduplicateKey')
            ->with('2024-02-01|source-123|ref-456')
            ->willReturn(null);

        $this->paymentRepository->expects($this->once())
            ->method('save')
            ->with(
                $this->isInstanceOf(Payment::class),
                '2024-02-01|source-123|ref-456',
            );

        $result = ($this->handler)($command);

        $this->assertInstanceOf(PaymentId::class, $result);
    }

    public function testCreatesPaymentImportWithNullableFields(): void
    {
        $commandWithNulls = new CreatePendingPaymentImport(
            new PaymentImportSourceIdentifier('source-789'),
            new BankReference('ref-789'), // Bank reference is required
            PaymentStatus::Initiated,
            new Money(1000, new Currency('USD')),
            null,
            new DateTimeImmutable('2024-02-03 00:00:00', new DateTimeZone('UTC')),
            null,
            null,
            null,
            null,
            null,
        );

        $this->paymentProjectionRepository->expects($this->once())
            ->method('findOne')
            ->with(
                null,
                null,
                new DateTimeImmutable('2024-02-03 00:00:00', new DateTimeZone('UTC')),
                new PaymentImportSourceIdentifier('source-789'),
                new BankReference('ref-789'),
            )
            ->willReturn(null);

        $this->paymentRepository->expects($this->once())
            ->method('getIdByDeduplicateKey')
            ->with('2024-02-03|source-789|ref-789')
            ->willReturn(null);

        $this->paymentRepository->expects($this->once())
            ->method('save')
            ->with(
                $this->isInstanceOf(Payment::class),
                '2024-02-03|source-789|ref-789',
            );

        $result = ($this->handler)($commandWithNulls);

        $this->assertInstanceOf(PaymentId::class, $result);
    }
}
