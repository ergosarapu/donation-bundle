<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\CreatePayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\CreatePaymentHandler;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\BankReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\GatewayReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\LegacyPaymentNumber;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ExternalEntityId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class CreatePaymentHandlerTest extends TestCase
{
    private CreatePaymentHandler $handler;
    private PaymentRepositoryInterface&MockObject $paymentRepository;
    private DateTimeImmutable $now;
    private CreatePayment $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2024-02-01 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new CreatePaymentHandler(
            $this->paymentRepository,
            $clock
        );

        $paymentId = PaymentId::generate();
        $amount = new Money(5000, new Currency('EUR'));
        $description = new ShortDescription('Test payment');
        $appliedTo = ExternalEntityId::generate();
        $email = new Email('donor@example.com');
        $name = new PersonName('John', 'Doe');
        $nationalIdCode = new NationalIdCode('12345678901');
        $initiatedAt = new DateTimeImmutable('2024-02-01');
        $capturedAt = new DateTimeImmutable('2024-02-02');
        $gatewayReference = new GatewayReference('gateway-tx-123');
        $bankReference = new BankReference('bank-ref-456');
        $paymentReference = new PaymentReference('1234567890');
        $legacyPaymentIdentifier = @new LegacyPaymentNumber('legacy-789');
        $iban = new Iban('GB94BARC10201530093459');
        $gateway = new Gateway('test-gateway');

        $this->command = new CreatePayment(
            $paymentId,
            PaymentStatus::Initiated,
            $amount,
            $description,
            $gateway,
            $email,
            $name,
            $nationalIdCode,
            $appliedTo,
            $initiatedAt,
            $capturedAt,
            $gatewayReference,
            $bankReference,
            $paymentReference,
            $legacyPaymentIdentifier,
            $iban,
        );
    }

    public function testCreatesPayment(): void
    {
        $this->paymentRepository->expects($this->once())
            ->method('has')
            ->with($this->command->paymentId)
            ->willReturn(false);

        $this->paymentRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Payment::class));

        ($this->handler)($this->command);
    }

    public function testIgnoresCommandWhenPaymentAlreadyExists(): void
    {
        $this->paymentRepository->expects($this->once())
            ->method('has')
            ->with($this->command->paymentId)
            ->willReturn(true);

        $this->paymentRepository->expects($this->never())
            ->method('save');

        ($this->handler)($this->command);
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
        ($this->handler)($this->command);
    }
}
