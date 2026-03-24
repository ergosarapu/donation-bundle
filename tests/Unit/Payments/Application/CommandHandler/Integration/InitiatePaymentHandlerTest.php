<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Application\CommandHandler\Integration;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\InitiatePayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\Integration\InitiatePaymentHandler;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command\InitiatePaymentIntegrationCommand;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandResult;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ExternalEntityId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InitiatePaymentHandlerTest extends TestCase
{
    private InitiatePaymentHandler $handler;
    private CommandBusInterface&MockObject $commandBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->handler = new InitiatePaymentHandler($this->commandBus);
    }

    public function testDispatchesInitiatePaymentCommand(): void
    {
        $paymentId = PaymentId::generate();
        $amount = new Money(5000, new Currency('EUR'));
        $gateway = new Gateway('test-gateway');
        $description = new ShortDescription('Test donation');
        $appliedTo = ExternalEntityId::generate();
        $email = new Email('donor@example.com');
        $requestPaymentMethodId = PaymentMethodId::generate();

        $integrationCommand = new InitiatePaymentIntegrationCommand(
            $paymentId,
            $amount,
            $gateway,
            $description,
            $appliedTo,
            $email,
            $requestPaymentMethodId,
            false
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) use ($paymentId, $amount, $gateway, $description, $appliedTo, $email, $requestPaymentMethodId) {
                if (!$command instanceof InitiatePayment) {
                    return false;
                }
                $request = $command->paymentRequest;
                return $request->paymentId === $paymentId
                    && $request->amount === $amount
                    && $request->gateway === $gateway
                    && $request->description === $description
                    && $request->appliedTo === $appliedTo
                    && $request->email === $email
                    && $request->paymentMethodAction !== null
                    && $request->paymentMethodAction->paymentMethodId === $requestPaymentMethodId;
            }))
            ->willReturn(new CommandResult(null, 'test-correlation-id'));

        ($this->handler)($integrationCommand);
    }

    public function testDispatchesInitiatePaymentCommandWithUsePaymentMethod(): void
    {
        $paymentId = PaymentId::generate();
        $amount = new Money(5000, new Currency('EUR'));
        $gateway = new Gateway('test-gateway');
        $description = new ShortDescription('Test donation');
        $appliedTo = ExternalEntityId::generate();
        $email = new Email('donor@example.com');
        $usePaymentMethodId = PaymentMethodId::generate();

        $integrationCommand = new InitiatePaymentIntegrationCommand(
            $paymentId,
            $amount,
            $gateway,
            $description,
            $appliedTo,
            $email,
            $usePaymentMethodId,
            true
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) use ($paymentId, $amount, $gateway, $description, $appliedTo, $email, $usePaymentMethodId) {
                if (!$command instanceof InitiatePayment) {
                    return false;
                }
                $request = $command->paymentRequest;
                return $request->paymentId === $paymentId
                    && $request->amount === $amount
                    && $request->gateway === $gateway
                    && $request->description === $description
                    && $request->appliedTo === $appliedTo
                    && $request->email === $email
                    && $request->paymentMethodAction !== null
                    && $request->paymentMethodAction->paymentMethodId === $usePaymentMethodId;
            }))
            ->willReturn(new CommandResult(null, 'test-correlation-id'));

        ($this->handler)($integrationCommand);
    }

    public function testDispatchesInitiatePaymentCommandWithoutPaymentMethod(): void
    {
        $paymentId = PaymentId::generate();
        $amount = new Money(5000, new Currency('EUR'));
        $gateway = new Gateway('test-gateway');
        $description = new ShortDescription('Test donation');
        $appliedTo = ExternalEntityId::generate();
        $email = new Email('donor@example.com');

        $integrationCommand = new InitiatePaymentIntegrationCommand(
            $paymentId,
            $amount,
            $gateway,
            $description,
            $appliedTo,
            $email
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) use ($paymentId, $amount, $gateway, $description, $appliedTo, $email) {
                if (!$command instanceof InitiatePayment) {
                    return false;
                }
                $request = $command->paymentRequest;
                return $request->paymentId === $paymentId
                    && $request->amount === $amount
                    && $request->gateway === $gateway
                    && $request->description === $description
                    && $request->appliedTo === $appliedTo
                    && $request->email === $email
                    && $request->paymentMethodAction === null;
            }))
            ->willReturn(new CommandResult(null, 'test-correlation-id'));

        ($this->handler)($integrationCommand);
    }

}
