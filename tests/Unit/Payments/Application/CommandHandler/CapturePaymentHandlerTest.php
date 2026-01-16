<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\CapturePayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsCaptured;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsFailed;
use ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\CapturePaymentHandler;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\GatewayCaptureResult;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentGatewayInterface;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\GatewayPaymentRequest;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCredentialValue;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class CapturePaymentHandlerTest extends TestCase
{
    private Payment&MockObject $payment;
    private CapturePaymentHandler $handler;
    private PaymentRepositoryInterface&MockObject $paymentRepository;
    private PaymentGatewayInterface&MockObject $paymentGateway;
    private CommandBusInterface&MockObject $commandBus;
    private DateTimeImmutable $now;
    private CapturePayment $command;
    private GatewayPaymentRequest $gatewayRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = $this->createMock(Payment::class);
        $this->paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $this->paymentGateway = $this->createMock(PaymentGatewayInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->now = new DateTimeImmutable('2024-02-01 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new CapturePaymentHandler(
            $this->paymentRepository,
            $clock,
            $this->paymentGateway,
            $this->commandBus
        );

        $paymentId = PaymentId::generate();
        $credentialValue = new PaymentCredentialValue('credential_token_123');
        $this->command = new CapturePayment($paymentId, $credentialValue);

        $this->gatewayRequest = new GatewayPaymentRequest(
            $paymentId,
            new Money(5000, new Currency('EUR')),
            new Gateway('montonio'),
            new ShortDescription('Test donation'),
            new Email('donor@example.com'),
        );
    }

    public function testCapturePayment(): void
    {
        $methodResult = PaymentMethodResult::usable(new PaymentCredentialValue('example_token'));

        $capturedAmount = new Money(5000, new Currency('EUR'));
        $captureResult = $this->createMock(GatewayCaptureResult::class);
        $captureResult->method('isSuccess')->willReturn(true);
        $captureResult->method('getCapturedAmount')->willReturn($capturedAmount);
        $captureResult->method('getPaymentMethodResult')->willReturn($methodResult);

        $this->paymentRepository->expects($this->once())
            ->method('load')
            ->with($this->command->paymentId)
            ->willReturn($this->payment);
        $this->payment->expects($this->once())
            ->method('reserveGatewayCall')
            ->with($this->now)
            ->willReturn($this->gatewayRequest);
        $this->paymentRepository->expects($this->once())
            ->method('save')
            ->with($this->payment);
        $this->paymentGateway->expects($this->once())
            ->method('capture')
            ->with($this->gatewayRequest, $this->command->credentialValue)
            ->willReturn($captureResult);
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) use ($capturedAmount, $methodResult) {
                return $command instanceof MarkPaymentAsCaptured &&
                    $command->paymentId === $this->command->paymentId &&
                    $command->capturedAmount->equals($capturedAmount) &&
                    $command->paymentMethodResult === $methodResult;
            }));

        ($this->handler)($this->command);
    }

    public function testCapturePaymentFailure(): void
    {
        $captureResult = $this->createMock(GatewayCaptureResult::class);
        $captureResult->method('isSuccess')->willReturn(false);
        $captureResult->method('getPaymentMethodResult')->willReturn(null);

        $this->paymentRepository->expects($this->once())
            ->method('load')
            ->with($this->command->paymentId)
            ->willReturn($this->payment);
        $this->payment->expects($this->once())
            ->method('reserveGatewayCall')
            ->with($this->now)
            ->willReturn($this->gatewayRequest);
        $this->paymentRepository->expects($this->once())
            ->method('save')
            ->with($this->payment);
        $this->paymentGateway->expects($this->once())
            ->method('capture')
            ->with($this->gatewayRequest, $this->command->credentialValue)
            ->willReturn($captureResult);
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) {
                return $command instanceof MarkPaymentAsFailed &&
                    $command->paymentId === $this->command->paymentId;
            }));

        ($this->handler)($this->command);
    }

    public function testCapturePaymentTransientFailure(): void
    {
        $captureResult = $this->createMock(GatewayCaptureResult::class);
        $captureResult->method('isSuccess')->willReturn(false);
        $captureResult->method('isTransientFailure')->willReturn(true);

        $this->paymentRepository->expects($this->once())
            ->method('load')
            ->with($this->command->paymentId)
            ->willReturn($this->payment);
        $this->payment->expects($this->once())
            ->method('reserveGatewayCall')
            ->with($this->now)
            ->willReturn($this->gatewayRequest);
        $this->paymentGateway->expects($this->once())
            ->method('capture')
            ->with($this->gatewayRequest, $this->command->credentialValue)
            ->willReturn($captureResult);
        $this->payment->expects($this->once())
            ->method('releaseGatewayCall')
            ->with($this->now);
        $this->paymentRepository->expects($this->exactly(2))
            ->method('save')
            ->with($this->payment);
        $this->commandBus->expects($this->never())
            ->method('dispatch');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Transient error during payment capture, retry later.');

        ($this->handler)($this->command);
    }

    public function testIgnoreCommandWhenGatewayCallAlreadyReserved(): void
    {
        $this->paymentRepository->expects($this->once())
            ->method('load')
            ->with($this->command->paymentId)
            ->willReturn($this->payment);
        $this->payment->expects($this->once())
            ->method('reserveGatewayCall')
            ->with($this->now)
            ->willReturn(null);
        $this->paymentGateway->expects($this->never())
            ->method('capture');
        $this->paymentRepository->expects($this->never())
            ->method('save');
        $this->commandBus->expects($this->never())
            ->method('dispatch');

        ($this->handler)($this->command);
    }
}
