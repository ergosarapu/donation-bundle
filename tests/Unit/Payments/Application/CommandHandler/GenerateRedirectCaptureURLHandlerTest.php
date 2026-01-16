<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\GenerateRedirectCaptureUrl;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsFailed;
use ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\GenerateRedirectCaptureURLHandler;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentGatewayInterface;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\GatewayPaymentRequest;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\URL;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class GenerateRedirectCaptureURLHandlerTest extends TestCase
{
    private Payment&MockObject $payment;
    private GenerateRedirectCaptureURLHandler $handler;
    private PaymentRepositoryInterface&MockObject $paymentRepository;
    private PaymentGatewayInterface&MockObject $paymentGateway;
    private CommandBusInterface&MockObject $commandBus;
    private DateTimeImmutable $now;
    private GenerateRedirectCaptureUrl $command;
    private GatewayPaymentRequest $gatewayPaymentRequest;

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

        $this->handler = new GenerateRedirectCaptureURLHandler(
            $this->paymentRepository,
            $this->paymentGateway,
            $clock,
            $this->commandBus
        );

        $paymentId = PaymentId::generate();
        $amount = new Money(5000, new Currency('EUR'));
        $gateway = new Gateway('montonio');
        $description = new ShortDescription('Test donation');
        $email = new Email('donor@example.com');
        $this->command = new GenerateRedirectCaptureUrl($paymentId, $amount, $gateway, $description, $email, false);
        $this->gatewayPaymentRequest = new GatewayPaymentRequest(
            $this->command->paymentId,
            $this->command->amount,
            $this->command->gateway,
            $this->command->description,
            $this->command->email,
        );
    }

    public function testGenerateRedirectUrl(): void
    {
        $expectedRedirectUrl = new URL('https://gateway.example.com/pay/123456');

        $this->payment->expects($this->once())
            ->method('reserveGatewayCall')
            ->with($this->now)
            ->willReturn($this->gatewayPaymentRequest);
        $this->paymentRepository->expects($this->once())
            ->method('load')
            ->with($this->command->paymentId)
            ->willReturn($this->payment);
        $this->paymentRepository->expects($this->exactly(2))
            ->method('save')
            ->with($this->payment);
        $this->paymentGateway->expects($this->once())
            ->method('createCaptureRedirectUrl')
            ->willReturn($expectedRedirectUrl);
        $this->commandBus->expects($this->never())
            ->method('dispatch');
        $this->payment->expects($this->once())
            ->method('setRedirectURL')
            ->with($this->now, $expectedRedirectUrl);

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
            ->method('createCaptureRedirectUrl');
        $this->paymentRepository->expects($this->never())
            ->method('save');
        $this->commandBus->expects($this->never())
            ->method('dispatch');

        ($this->handler)($this->command);
    }

    public function testUrlGenerationFails(): void
    {
        $this->payment->expects($this->once())
            ->method('reserveGatewayCall')
            ->with($this->now)
            ->willReturn($this->gatewayPaymentRequest);
        $this->paymentRepository->expects($this->once())
            ->method('load')
            ->with($this->command->paymentId)
            ->willReturn($this->payment);
        $this->paymentRepository->expects($this->once())
            ->method('save')
            ->with($this->payment);
        $this->paymentGateway->expects($this->once())
            ->method('createCaptureRedirectUrl')
            ->willReturn(null);
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) {
                return $command instanceof MarkPaymentAsFailed &&
                    $command->paymentId === $this->command->paymentId &&
                    $command->paymentMethodResult === null;
            }));

        ($this->handler)($this->command);
    }
}
