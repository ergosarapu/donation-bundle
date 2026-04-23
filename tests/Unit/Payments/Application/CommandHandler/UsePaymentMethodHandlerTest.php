<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsFailed;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\UsePaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\UsePaymentMethodHandler;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentMethodRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodAction;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class UsePaymentMethodHandlerTest extends TestCase
{
    private PaymentMethod&MockObject $paymentMethod;
    private UsePaymentMethodHandler $handler;
    private PaymentMethodRepositoryInterface&MockObject $paymentMethodRepository;
    private CommandBusInterface&MockObject $commandBus;
    private DateTimeImmutable $now;
    private UsePaymentMethod $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentMethod = $this->createMock(PaymentMethod::class);
        $this->paymentMethodRepository = $this->createMock(PaymentMethodRepositoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->now = new DateTimeImmutable('2024-02-01 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new UsePaymentMethodHandler(
            $this->paymentMethodRepository,
            $clock,
            $this->commandBus
        );

        $methodAction = PaymentMethodAction::forUse(
            PaymentMethodId::generate(),
            PaymentId::generate()
        );
        $this->command = new UsePaymentMethod($methodAction);
    }

    public function testUsesPaymentMethod(): void
    {
        $this->paymentMethodRepository->expects($this->once())
            ->method('has')
            ->with($this->command->paymentMethodAction->paymentMethodId)
            ->willReturn(true);
        $this->paymentMethodRepository->expects($this->once())
            ->method('load')
            ->with($this->command->paymentMethodAction->paymentMethodId)
            ->willReturn($this->paymentMethod);
        $this->paymentMethod->expects($this->once())
            ->method('use')
            ->with($this->now, $this->command->paymentMethodAction);
        $this->paymentMethodRepository->expects($this->once())
            ->method('save')
            ->with($this->paymentMethod);
        $this->commandBus->expects($this->never())
            ->method('dispatch');

        ($this->handler)($this->command);
    }

    public function testMarksPaymentAsFailedWhenPaymentMethodDoesNotExist(): void
    {
        $this->paymentMethodRepository->expects($this->once())
            ->method('has')
            ->with($this->command->paymentMethodAction->paymentMethodId)
            ->willReturn(false);
        $this->paymentMethodRepository->expects($this->never())
            ->method('load');
        $this->paymentMethodRepository->expects($this->never())
            ->method('save');
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) {
                return $command instanceof MarkPaymentAsFailed &&
                    $command->paymentId === $this->command->paymentMethodAction->paymentId &&
                    $command->paymentMethodResult === null;
            }))
            ->willReturn(new CommandResult(null, 'test-correlation-id'));

        ($this->handler)($this->command);
    }
}
