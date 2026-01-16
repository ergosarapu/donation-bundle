<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\StorePaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\StorePaymentMethodHandler;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentMethodRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCredentialValue;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodAction;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodlId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class StorePaymentMethodHandlerTest extends TestCase
{
    private StorePaymentMethodHandler $handler;
    private PaymentMethodRepositoryInterface&MockObject $paymentMethodRepository;
    private DateTimeImmutable $now;
    private StorePaymentMethod $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentMethodRepository = $this->createMock(PaymentMethodRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2024-02-01 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new StorePaymentMethodHandler(
            $this->paymentMethodRepository,
            $clock
        );

        $methodAction = PaymentMethodAction::forRequest(
            PaymentMethodlId::generate(),
            PaymentId::generate()
        );
        $methodResult = PaymentMethodResult::usable(
            new PaymentCredentialValue('token_123')
        );
        $this->command = new StorePaymentMethod($methodAction, $methodResult);
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
