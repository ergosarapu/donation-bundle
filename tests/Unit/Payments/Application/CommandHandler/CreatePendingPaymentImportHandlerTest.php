<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\CreatePendingPaymentImport;
use ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\CreatePendingPaymentImportHandler;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\AccountHolderName;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\BankReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Bic;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportSourceIdentifier;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\OrganisationRegCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class CreatePendingPaymentImportHandlerTest extends TestCase
{
    private CreatePendingPaymentImportHandler $handler;
    private PaymentRepositoryInterface&MockObject $paymentRepository;
    private DateTimeImmutable $now;
    private CreatePendingPaymentImport $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2024-02-01 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new CreatePendingPaymentImportHandler(
            $this->paymentRepository,
            $clock
        );

        $sourceIdentifier = new PaymentImportSourceIdentifier('source-123');
        $bankReference = new BankReference('ref-456');
        $amount = new Money(5000, new Currency('EUR'));
        $description = new ShortDescription('Test payment import');
        $bookingDate = new DateTimeImmutable('2024-02-02 12:01:15');
        $accountHolderName = new AccountHolderName('John Doe');
        $nationalIdCode = new NationalIdCode('12345678901');
        $organizationRegCode = new OrganisationRegCode('12345678');
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
            $nationalIdCode,
            $organizationRegCode,
            $referenceNumber,
            $iban,
            $bic,
        );
    }

    public function testCreatesPaymentImport(): void
    {
        $this->paymentRepository->expects($this->once())
            ->method('has')
            ->with($this->isInstanceOf(PaymentId::class))
            ->willReturn(false);

        $this->paymentRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Payment::class));

        $result = ($this->handler)($this->command);

        $this->assertInstanceOf(PaymentId::class, $result);
    }

    public function testIgnoresCommandWhenPaymentAlreadyExists(): void
    {
        $this->paymentRepository->expects($this->once())
            ->method('has')
            ->with($this->isInstanceOf(PaymentId::class))
            ->willReturn(true);

        $this->paymentRepository->expects($this->never())
            ->method('save');

        $result = ($this->handler)($this->command);

        $this->assertNull($result);
    }

    public function testHandlesAggregateAlreadyExistsException(): void
    {
        $this->paymentRepository->expects($this->once())
            ->method('has')
            ->willReturn(false);

        $this->paymentRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new AggregateAlreadyExistsException('Payment already exists'));

        // Should not throw exception - idempotency handling
        $result = ($this->handler)($this->command);

        $this->assertNull($result);
    }

    public function testGeneratesDeterministicPaymentIdFromSourceIdentifierBankReferenceAndBookingDate(): void
    {
        // This test locks down the exact arguments passed to PaymentId::generateDeterministic.
        // If the key composition or timestamp derivation changes in the handler, this test breaks.
        $expectedId = PaymentId::generateDeterministic(
            'source-123|ref-456',                                                       // sourceIdentifier|bankReference
            (new DateTimeImmutable('2024-02-02'))->setTime(0, 0, 0, 0)->getTimestamp() * 1000
        );

        $this->paymentRepository->method('has')->willReturn(false);
        $this->paymentRepository->method('save');

        $command = new CreatePendingPaymentImport(
            new PaymentImportSourceIdentifier('source-123'),
            new BankReference('ref-456'),
            PaymentStatus::Initiated,
            new Money(5000, new Currency('EUR')),
            new ShortDescription('Test deterministic ID'),
            new DateTimeImmutable('2024-02-02 15:30:45'), // Booking date with time component
            new AccountHolderName('John Doe'),
            new NationalIdCode('12345678901'),
            new OrganisationRegCode('12345678'),
            new PaymentReference('1234567890'),
            new Iban('EE382200221020145685'),
            new Bic('HABAEE2X'),
        );
        $result = ($this->handler)($command);

        $this->assertNotNull($result);
        $this->assertSame($expectedId->toString(), $result->toString());
    }

    public function testCreatesPaymentImportWithNullableFields(): void
    {
        $commandWithNulls = new CreatePendingPaymentImport(
            new PaymentImportSourceIdentifier('source-789'),
            new BankReference('ref-789'), // Bank reference is required
            PaymentStatus::Initiated,
            new Money(1000, new Currency('USD')),
            null,
            new DateTimeImmutable('2024-02-03'),
            null,
            null,
            null,
            null,
            null,
            null,
        );

        $this->paymentRepository->expects($this->once())
            ->method('has')
            ->with($this->isInstanceOf(PaymentId::class))
            ->willReturn(false);

        $this->paymentRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Payment::class));

        $result = ($this->handler)($commandWithNulls);

        $this->assertInstanceOf(PaymentId::class, $result);
    }
}
