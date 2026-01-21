<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\InitiatePaymentHandler;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodAction;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command\InitiatePaymentIntegrationCommand;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class InitiatePaymentHandlerTest extends TestCase
{
    private InitiatePaymentHandler $handler;
    private PaymentRepositoryInterface&MockObject $paymentRepository;
    private DateTimeImmutable $now;
    private InitiatePaymentIntegrationCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2024-02-01 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new InitiatePaymentHandler(
            $this->paymentRepository,
            $clock
        );

        $paymentId = PaymentId::generate();
        $amount = new Money(5000, new Currency('EUR'));
        $gateway = new Gateway('montonio');
        $description = new ShortDescription('Test donation');
        $appliedTo = PaymentAppliedToId::generate();
        $email = new Email('donor@example.com');
        $methodAction = PaymentMethodAction::forRequest(
            PaymentMethodId::generate(),
            $paymentId
        );

        $this->command = new InitiatePaymentIntegrationCommand(
            $paymentId,
            $amount,
            $gateway,
            $description,
            $appliedTo,
            $email,
            $methodAction
        );
    }

    public function testInitiatesPayment(): void
    {
        $this->paymentRepository->expects($this->once())
            ->method('has')
            ->with($this->command->paymentId)
            ->willReturn(false);
        $this->paymentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($payment) {
                return $payment instanceof Payment;
            }));

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
            ->with($this->command->paymentId)
            ->willReturn(false);
        $this->paymentRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new AggregateAlreadyExistsException('Payment already exists'));

        // Should not throw exception - idempotency handling
        ($this->handler)($this->command);
    }
}
