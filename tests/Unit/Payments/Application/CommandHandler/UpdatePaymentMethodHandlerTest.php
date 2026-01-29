<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\UpdatePaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\UpdatePaymentMethodHandler;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentMethodRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCredentialValue;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class UpdatePaymentMethodHandlerTest extends TestCase
{
    private PaymentMethod&MockObject $paymentMethod;
    private UpdatePaymentMethodHandler $handler;
    private PaymentMethodRepositoryInterface&MockObject $paymentMethodRepository;
    private DateTimeImmutable $now;
    private UpdatePaymentMethod $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentMethod = $this->createMock(PaymentMethod::class);
        $this->paymentMethodRepository = $this->createMock(PaymentMethodRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2024-02-01 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new UpdatePaymentMethodHandler(
            $this->paymentMethodRepository,
            $clock
        );

        $paymentMethodId = PaymentMethodId::generate();
        $methodResult = PaymentMethodResult::usable(
            new PaymentCredentialValue('updated_token_456')
        );
        $this->command = new UpdatePaymentMethod($paymentMethodId, $methodResult);
    }

    public function testUpdatesPaymentCredential(): void
    {
        $this->paymentMethodRepository->expects($this->once())
            ->method('load')
            ->with($this->command->paymentMethodId)
            ->willReturn($this->paymentMethod);
        $this->paymentMethod->expects($this->once())
            ->method('update')
            ->with($this->now, $this->command->paymentMethodId, $this->command->result);
        $this->paymentMethodRepository->expects($this->once())
            ->method('save')
            ->with($this->paymentMethod);

        ($this->handler)($this->command);
    }
}
