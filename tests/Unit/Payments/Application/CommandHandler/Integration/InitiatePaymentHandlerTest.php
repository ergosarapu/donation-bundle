<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Application\CommandHandler\Integration;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\InitiatePayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\Integration\InitiatePaymentHandler;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodActionIntent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command\InitiatePaymentIntegrationCommand;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandResult;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ExternalEntityId;
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

    public function testDispatchesInitiatePaymentCommandWithUsePaymentMethod(): void
    {
        $amount = new Money(5000, new Currency('EUR'));
        $gateway = new Gateway('test-gateway');
        $description = new ShortDescription('Test donation');
        $appliedTo = ExternalEntityId::generate();
        $email = new Email('donor@example.com');
        $usePaymentMethodId = ExternalEntityId::generate();

        $integrationCommand = new InitiatePaymentIntegrationCommand(
            $amount,
            $gateway,
            $description,
            $appliedTo,
            $email,
            $usePaymentMethodId,
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) use ($amount, $gateway, $description, $appliedTo, $email, $usePaymentMethodId) {
                if (!$command instanceof InitiatePayment) {
                    return false;
                }
                $request = $command->paymentRequest;
                return $request->amount === $amount
                    && $request->gateway === $gateway
                    && $request->description === $description
                    && $request->appliedTo === $appliedTo
                    && $request->email === $email
                    && $request->paymentMethodAction !== null
                    && $request->paymentMethodAction->paymentMethodId->toString() === $usePaymentMethodId->toString();
            }))
            ->willReturn(new CommandResult(null, 'test-correlation-id'));

        ($this->handler)($integrationCommand);
    }

    public function testDispatchesInitiatePaymentCommandWithoutPaymentMethod(): void
    {
        $amount = new Money(5000, new Currency('EUR'));
        $gateway = new Gateway('test-gateway');
        $description = new ShortDescription('Test donation');
        $appliedTo = ExternalEntityId::generate();
        $email = new Email('donor@example.com');

        $integrationCommand = new InitiatePaymentIntegrationCommand(
            $amount,
            $gateway,
            $description,
            $appliedTo,
            $email
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) use ($amount, $gateway, $description, $appliedTo, $email) {
                if (!$command instanceof InitiatePayment) {
                    return false;
                }
                $request = $command->paymentRequest;
                return $request->amount === $amount
                    && $request->gateway === $gateway
                    && $request->description === $description
                    && $request->appliedTo === $appliedTo
                    && $request->email === $email
                    && $request->paymentMethodAction === null;
            }))
            ->willReturn(new CommandResult(null, 'test-correlation-id'));

        ($this->handler)($integrationCommand);
    }

    public function testDispatchesInitiatePaymentCommandWithRequestPaymentMethod(): void
    {
        $amount = new Money(5000, new Currency('EUR'));
        $gateway = new Gateway('test-gateway');
        $description = new ShortDescription('Test donation');
        $appliedTo = ExternalEntityId::generate();
        $email = new Email('donor@example.com');

        $integrationCommand = new InitiatePaymentIntegrationCommand(
            $amount,
            $gateway,
            $description,
            $appliedTo,
            $email,
            null,
            ExternalEntityId::generate(),
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) use ($amount, $gateway, $description, $appliedTo, $email) {
                if (!$command instanceof InitiatePayment) {
                    return false;
                }
                $request = $command->paymentRequest;
                return $request->amount === $amount
                    && $request->gateway === $gateway
                    && $request->description === $description
                    && $request->appliedTo === $appliedTo
                    && $request->email === $email
                    && $request->paymentMethodAction !== null
                    && $request->paymentMethodAction->intent === PaymentMethodActionIntent::Request;
            }))
            ->willReturn(new CommandResult(null, 'test-correlation-id'));

        ($this->handler)($integrationCommand);
    }

}
