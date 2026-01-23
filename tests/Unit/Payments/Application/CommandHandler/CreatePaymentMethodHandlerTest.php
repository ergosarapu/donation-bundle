<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\CreatePaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\CreatePaymentMethodHandler;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentMethodRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCredentialValue;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodAction;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class CreatePaymentMethodHandlerTest extends TestCase
{
    private CreatePaymentMethodHandler $handler;
    private PaymentMethodRepositoryInterface&MockObject $paymentMethodRepository;
    private DateTimeImmutable $now;
    private CreatePaymentMethod $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentMethodRepository = $this->createMock(PaymentMethodRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2024-02-01 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new CreatePaymentMethodHandler(
            $this->paymentMethodRepository,
            $clock
        );

        $methodAction = PaymentMethodAction::forRequest(
            PaymentId::generate()
        );
        $methodResult = PaymentMethodResult::usable(
            new PaymentCredentialValue('token_123')
        );
        $this->command = new CreatePaymentMethod($methodAction, $methodResult);
    }

    public function testCreatesPaymentMethod(): void
    {
        $this->paymentMethodRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($paymentMethod) {
                return $paymentMethod instanceof PaymentMethod;
            }));

        ($this->handler)($this->command);
    }

    public function testIgnoreCommandWhenGatewayCallAlreadyReserved(): void
    {
        $this->paymentMethodRepository->expects($this->once())
            ->method('has')
            ->with($this->command->paymentMethodAction->paymentMethodId)
            ->willReturn(true);
        $this->paymentMethodRepository->expects($this->never())
            ->method('save');

        // Should not throw exception - idempotency handling
        ($this->handler)($this->command);
    }

    public function testHandlesAggregateAlreadyExistsException(): void
    {
        $this->paymentMethodRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new AggregateAlreadyExistsException('Payment method already exists'));

        // Should not throw exception - idempotency handling
        ($this->handler)($this->command);
    }
}
