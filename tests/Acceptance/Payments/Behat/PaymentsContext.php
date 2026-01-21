<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Acceptance\Payments\Behat;

use Behat\Behat\Context\Context;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\CreatePaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsAuthorized;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsCanceled;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsCaptured;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsFailed;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentAuthorized;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCanceled;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCaptured;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCredentialValue;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentDidNotSucceed;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentFailed;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentInitiated;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodAction;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUnusable;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUnusableReason;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUsePermitted;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUseRejected;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentRedirectUrlSetUp;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentSucceeded;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\UnusablePaymentMethodCreated;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\UsablePaymentMethodCreated;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command\InitiatePaymentIntegrationCommand;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentDidNotSucceedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentMethodUnusableIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentSucceededIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\UnusablePaymentMethodCreatedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\UsablePaymentMethodCreatedIntegrationEvent;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\URL;
use ErgoSarapu\DonationBundle\Tests\Acceptance\Payments\FakeGateway;
use Exception;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngine;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Webmozart\Assert\Assert;
use Zenstruck\Messenger\Test\Transport\TestTransport;

class PaymentsContext implements Context
{
    private PaymentId $lastPaymentId;
    private PaymentMethodId $lastPaymentMethodId;

    public function __construct(
        #[Autowire(service: 'messenger.transport.command')]
        private readonly TestTransport $commandTransport,
        #[Autowire(service: 'messenger.transport.integration_command')]
        private readonly TestTransport $integrationCommandTransport,
        #[Autowire(service: 'messenger.transport.event')]
        private readonly TestTransport $eventTransport,
        #[Autowire(service: 'messenger.transport.integration_event')]
        private readonly TestTransport $integrationEventTransport,
        private readonly SubscriptionEngine $subscriptionEngine,
        private readonly FakeGateway $gateway,
        private readonly CommandBusInterface $commandBus,
    ) {
        $this->subscriptionEngine->setup();
        $this->subscriptionEngine->boot();
    }

    #[BeforeScenario]
    public function resetTransports(): void
    {
        $this->eventTransport->reset();
        $this->integrationEventTransport->reset();
        $this->commandTransport->reset();
        $this->integrationCommandTransport->reset();
    }

    private function dispatchInitiatePaymentCommand(InitiatePaymentIntegrationCommand $command): void
    {
        $this->commandBus->dispatch($command);
        $this->integrationCommandTransport->processOrFail();
    }

    private function parsePaymentMethodResult(string $paymentMethodResult): ?PaymentMethodResult
    {
        return match ($paymentMethodResult) {
            'usable' => PaymentMethodResult::usable(new PaymentCredentialValue('credential-value')),
            'unusable' => PaymentMethodResult::unusable(PaymentMethodUnusableReason::Expired),
            'no' => null,
            default => throw new Exception('Unknown payment method result: ' . $paymentMethodResult),
        };
    }

    private function getDefaultTestMoney(): Money
    {
        return new Money(1000, new Currency('EUR'));
    }

    private function createInitiatePaymentCommand(?PaymentMethodAction $paymentMethodAction = null): InitiatePaymentIntegrationCommand
    {
        return new InitiatePaymentIntegrationCommand(
            $this->lastPaymentId,
            $this->getDefaultTestMoney(),
            new Gateway('test-gateway'),
            new ShortDescription('Test Payment'),
            PaymentAppliedToId::generate(),
            new Email('test@example.com'),
            $paymentMethodAction,
        );
    }

    #[Given('gateway returns a redirect URL')]
    public function gatewayReturnsRedirectUrl(): void
    {
        $this->gateway->useCreateCaptureRedirectUrlBehavior(new URL('https://example.com/capture'));
    }

    #[Given('gateway does not return a redirect URL')]
    public function gatewayDoesNotReturnRedirectUrl(): void
    {
        $this->gateway->useCreateCaptureRedirectUrlBehavior(null);
    }

    #[Given('gateway captures payment with :paymentMethodResult payment method result')]
    public function gatewayCapturesPaymentWithPaymentMethodResult(string $paymentMethodResult): void
    {
        $result = $this->parsePaymentMethodResult($paymentMethodResult);
        $this->gateway->useCaptureBehavior(
            true,
            $this->getDefaultTestMoney(),
            paymentMethodResult: $result
        );
    }

    #[Given('gateway fails to capture payment with :paymentMethodResult payment method result')]
    public function gatewayFailsToCapturePaymentWithPaymentMethodResult(string $paymentMethodResult): void
    {
        $result = $this->parsePaymentMethodResult($paymentMethodResult);
        $this->gateway->useCaptureBehavior(success: false, paymentMethodResult: $result);
    }

    #[Given('usable payment method exists')]
    public function usablePaymentMethodExists(): void
    {
        $this->lastPaymentMethodId = PaymentMethodId::generate();
        $action = PaymentMethodAction::forRequest(
            $this->lastPaymentMethodId,
            PaymentId::generate(),
        );
        $this->commandBus->dispatch(new CreatePaymentMethod(
            $action,
            PaymentMethodResult::usable(new PaymentCredentialValue('credential-value')),
        ));
        $this->usablePaymentMethodIsCreated();
    }

    #[Given('unusable payment method exists')]
    public function unusablePaymentMethodExists(): void
    {
        $this->lastPaymentMethodId = PaymentMethodId::generate();
        $action = PaymentMethodAction::forRequest(
            $this->lastPaymentMethodId,
            PaymentId::generate(),
        );
        $this->commandBus->dispatch(new CreatePaymentMethod(
            $action,
            PaymentMethodResult::unusable(PaymentMethodUnusableReason::Expired),
        ));
        $this->unusablePaymentMethodIsCreated();
    }

    #[Given('payment method does not exist')]
    public function paymentMethodDoesNotExist(): void
    {
        // Generate payment method ID that is not stored
        $this->lastPaymentMethodId = PaymentMethodId::generate();
        // Store a different payment method to ensure the specified one does not exist,
        // allowing to test the non-existence scenario.
        $action = PaymentMethodAction::forRequest(
            PaymentMethodId::generate(),
            PaymentId::generate(),
        );
        $this->commandBus->dispatch(new CreatePaymentMethod(
            $action,
            PaymentMethodResult::usable(new PaymentCredentialValue('credential-value')),
        ));
    }

    #[Given('initiated payment exists')]
    public function initiatedPaymentExists(): void
    {
        $this->gatewayReturnsRedirectUrl();
        $this->initiatePayment();
        $this->paymentIsInitiated();
    }

    #[When('initiate payment')]
    public function initiatePayment(): void
    {
        $this->lastPaymentId = PaymentId::generate();
        $this->dispatchInitiatePaymentCommand($this->createInitiatePaymentCommand());
    }

    #[When('initiate payment with request to store payment method')]
    public function initiatePaymentWithRequestToStorePaymentMethod(): void
    {
        $this->lastPaymentId = PaymentId::generate();
        $action = PaymentMethodAction::forRequest(PaymentMethodId::generate(), $this->lastPaymentId);
        $this->dispatchInitiatePaymentCommand($this->createInitiatePaymentCommand($action));
    }

    #[When('initiate payment using stored payment method')]
    public function initiatePaymentUsingStoredPaymentMethod(): void
    {
        $this->lastPaymentId = PaymentId::generate();
        $action = PaymentMethodAction::forUse($this->lastPaymentMethodId, $this->lastPaymentId);
        $this->dispatchInitiatePaymentCommand($this->createInitiatePaymentCommand($action));
    }

    #[When('mark payment as authorized')]
    public function markPaymentAsAuthorized(): void
    {
        $this->commandBus->dispatch(
            new MarkPaymentAsAuthorized(
                $this->lastPaymentId,
                $this->getDefaultTestMoney(),
                null
            )
        );
    }

    #[When('mark payment as captured')]
    public function markPaymentAsCaptured(): void
    {
        $this->commandBus->dispatch(
            new MarkPaymentAsCaptured(
                $this->lastPaymentId,
                $this->getDefaultTestMoney(),
                null
            )
        );
    }

    #[When('mark payment as captured with :paymentMethodResult payment method result')]
    public function markPaymentAsCapturedWithPaymentMethodResult(?string $paymentMethodResult = null): void
    {
        $result = $paymentMethodResult ? $this->parsePaymentMethodResult($paymentMethodResult) : null;
        $this->commandBus->dispatch(
            new MarkPaymentAsCaptured(
                $this->lastPaymentId,
                $this->getDefaultTestMoney(),
                $result,
            )
        );
    }

    #[When('mark payment as failed')]
    public function markPaymentAsFailed(): void
    {
        $this->markPaymentAsFailedWithPaymentMethodResult(null);
    }

    #[When('mark payment as failed with :paymentMethodResult payment method result')]
    public function markPaymentAsFailedWithPaymentMethodResult(?string $paymentMethodResult = null): void
    {
        $result = $paymentMethodResult ? $this->parsePaymentMethodResult($paymentMethodResult) : null;
        $this->commandBus->dispatch(
            new MarkPaymentAsFailed(
                $this->lastPaymentId,
                $result,
            )
        );
    }

    #[When('mark payment as canceled')]
    public function markPaymentAsCanceled(): void
    {
        $this->commandBus->dispatch(
            new MarkPaymentAsCanceled(
                $this->lastPaymentId
            )
        );
    }

    #[Then('payment is initiated')]
    public function paymentIsInitiated(): void
    {
        // Assert event was dispatched
        $this->eventTransport->dispatched()->assertContains(PaymentInitiated::class, 1);

        // Get the event and verify its properties
        $messages = $this->eventTransport->dispatched()->messages(PaymentInitiated::class);

        /** @var PaymentInitiated $event */
        $event = $messages[0];
        Assert::eq($event->paymentId, $this->lastPaymentId, 'Payment ID does not match');
    }

    #[Then('payment is marked as authorized')]
    public function paymentIsMarkedAsAuthorized(): void
    {
        $this->eventTransport->dispatched()->assertContains(PaymentAuthorized::class, 1);
        $this->eventTransport->dispatched()->assertContains(PaymentSucceeded::class, 1);
    }

    #[Then('payment is marked as captured')]
    public function paymentIsMarkedAsCaptured(): void
    {
        $this->eventTransport->dispatched()->assertContains(PaymentCaptured::class, 1);
        $this->eventTransport->dispatched()->assertContains(PaymentSucceeded::class, 1);
    }

    #[Then('payment is marked as failed')]
    public function paymentIsMarkedAsFailed(): void
    {
        $this->eventTransport->dispatched()->assertContains(PaymentFailed::class, 1);
        $this->eventTransport->dispatched()->assertContains(PaymentDidNotSucceed::class, 1);
    }

    #[Then('payment is marked as canceled')]
    public function paymentIsMarkedAsCanceled(): void
    {
        $this->eventTransport->dispatched()->assertContains(PaymentCanceled::class, 1);
        $this->eventTransport->dispatched()->assertContains(PaymentDidNotSucceed::class, 1);
    }

    #[Then('payment redirect URL is set up')]
    public function paymentRedirectUrlIsSetUp(): void
    {
        $this->eventTransport->dispatched()->assertContains(PaymentRedirectUrlSetUp::class, 1);
    }

    #[Then('payment succeeded integration event is emitted')]
    public function paymentSucceededIntegrationEventIsEmitted(): void
    {
        $this->integrationEventTransport->queue()->assertContains(PaymentSucceededIntegrationEvent::class, 1);
    }

    #[Then('payment did not succeed integration event is emitted')]
    public function paymentDidNotSucceedIntegrationEventIsEmitted(): void
    {
        $this->integrationEventTransport->queue()->assertContains(PaymentDidNotSucceedIntegrationEvent::class, 1);
    }

    #[Then('usable payment method is created')]
    #[Then('stored payment method is usable')]
    public function usablePaymentMethodIsCreated(): void
    {
        $this->eventTransport->dispatched()->assertContains(UsablePaymentMethodCreated::class, 1);
    }

    #[Then('unusable payment method is created')]
    public function unusablePaymentMethodIsCreated(): void
    {
        $this->eventTransport->dispatched()->assertContains(UnusablePaymentMethodCreated::class, 1);
    }

    #[Then('stored payment method is unusable')]
    public function storedPaymentMethodIsUnusable(): void
    {
        $this->eventTransport->dispatched()->assertContains(PaymentMethodUnusable::class, 1);
    }

    #[Then('no payment method integration event is emitted')]
    public function noPaymentMethodIntegrationEventIsEmitted(): void
    {
        $this->integrationEventTransport->dispatched()->assertNotContains(PaymentMethodUnusableIntegrationEvent::class);
    }

    #[Then('unusable payment method integration event is emitted')]
    public function unusablePaymentMethodIntegrationEventIsEmitted(): void
    {
        $this->integrationEventTransport->dispatched()->assertContains(PaymentMethodUnusableIntegrationEvent::class, 1);
    }


    #[Then('usable payment method created integration event is emitted')]
    public function usablePaymentMethodCreatedIntegrationEventIsEmitted(): void
    {
        $this->integrationEventTransport->dispatched()->assertContains(UsablePaymentMethodCreatedIntegrationEvent::class, 1);
    }

    #[Then('unusable payment method created integration event is emitted')]
    public function unusablePaymentMethodCreatedIntegrationEventIsEmitted(): void
    {
        $this->integrationEventTransport->dispatched()->assertContains(UnusablePaymentMethodCreatedIntegrationEvent::class, 1);
    }

    #[Then('payment method use is permitted')]
    public function paymentMethodUseIsPermitted(): void
    {
        $this->eventTransport->dispatched()->assertContains(PaymentMethodUsePermitted::class, 1);
    }

    #[Then('payment method use is rejected')]
    public function paymentMethodUseIsRejected(): void
    {
        $this->eventTransport->dispatched()->assertContains(PaymentMethodUseRejected::class, 1);
    }

    #[Then('no payment method is stored')]
    public function noPaymentMethodIsStored(): void
    {
        $this->eventTransport->dispatched()->assertNotContains(UsablePaymentMethodCreated::class);
        $this->eventTransport->dispatched()->assertNotContains(PaymentMethodUnusable::class);
    }

}
