<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Application\CommandHandler\Integration;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\InitiatePayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\Integration\InitiatePaymentHandler;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodAction;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentRequest;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command\InitiatePaymentIntegrationCommand;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
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
        $appliedTo = PaymentAppliedToId::generate();
        $email = new Email('donor@example.com');
        $methodAction = PaymentMethodAction::forRequest(
            PaymentMethodId::generate(),
            $paymentId
        );

        $paymentRequest = new PaymentRequest(
            $paymentId,
            $amount,
            $gateway,
            $description,
            $appliedTo,
            $email,
            $methodAction
        );

        $integrationCommand = new InitiatePaymentIntegrationCommand(
            $paymentRequest
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) use ($paymentRequest) {
                return $command instanceof InitiatePayment
                    && $command->paymentRequest === $paymentRequest;
            }));

        ($this->handler)($integrationCommand);
    }
}
