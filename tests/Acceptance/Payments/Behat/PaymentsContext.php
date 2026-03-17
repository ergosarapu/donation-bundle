<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Acceptance\Payments\Behat;

use Behat\Behat\Context\Context;
use Behat\Hook\AfterScenario;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\CreatePayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\CreatePaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\ImportPaymentsFromFile;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsAuthorized;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsCanceled;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsCaptured;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsFailed;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentFileImportResult;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPendingPayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Iban;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCredentialValue;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportStatus;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodAction;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodActionIntent;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUnusable;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUnusableReason;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentRedirectUrlSetUp;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentRequest;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\UnusablePaymentMethodCreated;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\UsablePaymentMethodCreated;
use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationCommandInterface;
use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationEventInterface;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command\InitiatePaymentIntegrationCommand;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentDidNotSucceedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentMethodUnusableIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentSucceededIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\UnusablePaymentMethodCreatedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\UsablePaymentMethodCreatedIntegrationEvent;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\URL;
use ErgoSarapu\DonationBundle\Tests\Acceptance\Payments\FakeGateway;
use ErgoSarapu\DonationBundle\Tests\Helpers\TestCommandBus;
use ErgoSarapu\DonationBundle\Tests\Helpers\TestEventBus;
use Exception;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngine;
use Webmozart\Assert\Assert;

class PaymentsContext implements Context
{
    private PaymentId $lastPaymentId;
    private PaymentId $existingCapturedPaymentId;
    private PaymentMethodId $lastPaymentMethodId;
    private string $lastUploadedPaymentImportFile;

    public function __construct(
        private readonly SubscriptionEngine $subscriptionEngine,
        private readonly FakeGateway $gateway,
        private readonly TestCommandBus $commandBus,
        private readonly TestEventBus $eventBus,
        private readonly QueryBusInterface $queryBus,
    ) {
        $this->initProjections();
    }

    #[BeforeScenario]
    public function resetTransports(): void
    {
        $this->eventBus->reset();
        $this->eventBus->intercept(IntegrationEventInterface::class);
        $this->commandBus->reset();
        $this->commandBus->intercept(IntegrationCommandInterface::class);
    }

    #[BeforeScenario]
    public function initProjections(): void
    {
        $this->clearProjections();
        $this->subscriptionEngine->setup();
        $this->subscriptionEngine->boot();
    }

    #[AfterScenario]
    public function clearProjections(): void
    {
        $this->subscriptionEngine->remove();
    }

    private function sendInitiatePaymentCommand(InitiatePaymentIntegrationCommand $command): void
    {
        $this->commandBus->send($command);
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
        $paymentRequest = new PaymentRequest(
            $this->lastPaymentId,
            $this->getDefaultTestMoney(),
            new Gateway('test-gateway'),
            new ShortDescription('Test Payment'),
            PaymentAppliedToId::generate(),
            new Email('test@example.com'),
            $paymentMethodAction,
        );

        return new InitiatePaymentIntegrationCommand(
            paymentId: $paymentRequest->paymentId,
            amount: $paymentRequest->amount,
            gateway: $paymentRequest->gateway,
            description: $paymentRequest->description,
            appliedTo: $paymentRequest->appliedTo,
            email: $paymentRequest->email,
            paymentMethodId: $paymentMethodAction?->paymentMethodId,
            usePaymentMethodId: $paymentMethodAction?->intent === PaymentMethodActionIntent::Use,
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
        $paymentMethodId = PaymentMethodId::generate();
        $this->lastPaymentMethodId = $paymentMethodId;
        $this->commandBus->send(new CreatePaymentMethod(
            $paymentMethodId,
            PaymentMethodResult::usable(new PaymentCredentialValue('credential-value')),
        ));
        $this->usablePaymentMethodIsCreated();
    }

    #[Given('unusable payment method exists')]
    public function unusablePaymentMethodExists(): void
    {
        $paymentMethodId = PaymentMethodId::generate();
        $this->lastPaymentMethodId = $paymentMethodId;
        $this->commandBus->send(new CreatePaymentMethod(
            $paymentMethodId,
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
        $this->commandBus->send(new CreatePaymentMethod(
            PaymentMethodId::generate(),
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
        $this->sendInitiatePaymentCommand($this->createInitiatePaymentCommand());
    }

    #[When('initiate payment with request to store payment method')]
    public function initiatePaymentWithRequestToStorePaymentMethod(): void
    {
        $this->lastPaymentId = PaymentId::generate();
        $paymentMethodId = PaymentMethodId::generate();
        $this->lastPaymentMethodId = $paymentMethodId;
        $action = PaymentMethodAction::forRequest($paymentMethodId, $this->lastPaymentId);
        $this->sendInitiatePaymentCommand($this->createInitiatePaymentCommand($action));
    }

    #[When('initiate payment using stored payment method')]
    public function initiatePaymentUsingStoredPaymentMethod(): void
    {
        $this->lastPaymentId = PaymentId::generate();
        $action = PaymentMethodAction::forUse($this->lastPaymentMethodId, $this->lastPaymentId);
        $this->sendInitiatePaymentCommand($this->createInitiatePaymentCommand($action));
    }

    #[When('mark payment as authorized')]
    public function markPaymentAsAuthorized(): void
    {
        $this->commandBus->send(
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
        $this->commandBus->send(
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
        $this->commandBus->send(
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
        $this->commandBus->send(
            new MarkPaymentAsFailed(
                $this->lastPaymentId,
                $result,
            )
        );
    }

    #[When('mark payment as canceled')]
    public function markPaymentAsCanceled(): void
    {
        $this->commandBus->send(
            new MarkPaymentAsCanceled(
                $this->lastPaymentId
            )
        );
    }

    #[Then('payment is initiated')]
    public function paymentIsInitiated(): void
    {
        $payment = $this->queryBus->ask(new GetPendingPayment($this->lastPaymentId));
        Assert::isInstanceOf($payment, Payment::class);
        $this->lastPaymentId = PaymentId::fromString($payment->getPaymentId());
    }

    #[Then('payment is marked as :paymentState')]
    public function paymentIsMarkedAsAuthorized(string $paymentState): void
    {
        $payment = $this->queryBus->ask(new GetPayment($this->lastPaymentId));
        Assert::isInstanceOf($payment, Payment::class);
        Assert::eq($payment->getStatus(), PaymentStatus::from($paymentState));

    }

    #[Then('payment redirect URL is set up')]
    public function paymentRedirectUrlIsSetUp(): void
    {
        $this->eventBus->assertDispatched(PaymentRedirectUrlSetUp::class, 1);
    }

    #[Then('payment succeeded integration event is emitted')]
    public function paymentSucceededIntegrationEventIsEmitted(): void
    {
        $this->eventBus->assertDispatched(PaymentSucceededIntegrationEvent::class, 1);
    }

    #[Then('payment did not succeed integration event is emitted')]
    public function paymentDidNotSucceedIntegrationEventIsEmitted(): void
    {
        $this->eventBus->assertDispatched(PaymentDidNotSucceedIntegrationEvent::class, 1);
    }

    #[Then('usable payment method is created')]
    #[Then('stored payment method is usable')]
    public function usablePaymentMethodIsCreated(): void
    {
        $this->eventBus->assertDispatched(UsablePaymentMethodCreated::class, 1);
        // Verify the PaymentMethodId in the event matches the expected one
        $events = $this->eventBus->dispatchedMessages(UsablePaymentMethodCreated::class);
        /** @var UsablePaymentMethodCreated $event */
        $event = $events[0];
        Assert::eq(
            $event->paymentMethodId->toString(),
            $this->lastPaymentMethodId->toString(),
            'PaymentMethodId in UsablePaymentMethodCreated event should match the expected PaymentMethodId'
        );
    }

    #[Then('unusable payment method is created')]
    public function unusablePaymentMethodIsCreated(): void
    {
        $this->eventBus->assertDispatched(UnusablePaymentMethodCreated::class, 1);
        // Verify the PaymentMethodId in the event matches the expected one
        $events = $this->eventBus->dispatchedMessages(UnusablePaymentMethodCreated::class);
        /** @var UnusablePaymentMethodCreated $event */
        $event = $events[0];
        Assert::eq(
            $event->paymentMethodId->toString(),
            $this->lastPaymentMethodId->toString(),
            'PaymentMethodId in UnusablePaymentMethodCreated event should match the expected PaymentMethodId'
        );
    }

    #[Then('stored payment method is unusable')]
    public function storedPaymentMethodIsUnusable(): void
    {
        $this->eventBus->assertDispatched(PaymentMethodUnusable::class, 1);
        // Verify the PaymentMethodId in the event matches the expected one
        $events = $this->eventBus->dispatchedMessages(PaymentMethodUnusable::class);
        /** @var PaymentMethodUnusable $event */
        $event = $events[0];
        Assert::eq(
            $event->paymentMethodId->toString(),
            $this->lastPaymentMethodId->toString(),
            'PaymentMethodId in PaymentMethodUnusable event should match the expected PaymentMethodId'
        );
    }

    #[Then('no payment method integration event is emitted')]
    public function noPaymentMethodIntegrationEventIsEmitted(): void
    {
        $this->eventBus->assertNotDispatched(PaymentMethodUnusableIntegrationEvent::class);
    }

    #[Then('unusable payment method integration event is emitted')]
    public function unusablePaymentMethodIntegrationEventIsEmitted(): void
    {
        $this->eventBus->assertDispatched(PaymentMethodUnusableIntegrationEvent::class, 1);
    }


    #[Then('usable payment method created integration event is emitted')]
    public function usablePaymentMethodCreatedIntegrationEventIsEmitted(): void
    {
        $this->eventBus->assertDispatched(UsablePaymentMethodCreatedIntegrationEvent::class, 1);
    }

    #[Then('unusable payment method created integration event is emitted')]
    public function unusablePaymentMethodCreatedIntegrationEventIsEmitted(): void
    {
        $this->eventBus->assertDispatched(UnusablePaymentMethodCreatedIntegrationEvent::class, 1);
    }

    #[Then('no payment method is stored')]
    public function noPaymentMethodIsStored(): void
    {
        $this->eventBus->assertNotDispatched(UsablePaymentMethodCreated::class);
        $this->eventBus->assertNotDispatched(PaymentMethodUnusable::class);
    }

    #[Given(':fileName has been uploaded')]
    public function paymentImportFileHasBeenUploaded(string $fileName): void
    {
        $this->lastUploadedPaymentImportFile = __DIR__ . '/../../../Unit/Payments/Infrastructure/Fixtures/' . $fileName;
    }

    #[When('import payments from file')]
    public function importPaymentsFromFile(): void
    {
        $commandResult = $this->commandBus->send(new ImportPaymentsFromFile($this->lastUploadedPaymentImportFile));
        Assert::isInstanceOf($commandResult->result, PaymentFileImportResult::class);
        /** @var PaymentFileImportResult $result */
        $result = $commandResult->result;
        Assert::greaterThan(count($result->pendingPaymentIds), 0);
        $this->lastPaymentId = $result->pendingPaymentIds[0];
    }

    #[Given('payment with same details as in single_entry_private_debtor.camt.xml already exists')]
    public function paymentWithSameDetailsAlreadyExists(): void
    {
        // Create a payment with the same details as the one in single_entry_private_debtor.camt.xml
        // This will allow automatic reconciliation when the file is imported
        $this->existingCapturedPaymentId = PaymentId::generate();

        $command = new CreatePayment(
            paymentId: $this->existingCapturedPaymentId,
            status: PaymentStatus::Captured,
            amount: new Money(10000, new Currency('EUR')), // 100.00 EUR
            description: new ShortDescription('Donation'),
            gateway: null,
            email: null,
            name: new PersonName('Mati', 'Karu'),
            nationalIdCode: new NationalIdCode('39876543210'),
            paymentAppliedToId: null,
            initiatedAt: new \DateTimeImmutable('2025-11-24'),
            capturedAt: new \DateTimeImmutable('2025-11-24'),
            gatewayTransactionId: null,
            bankReference: null,
            paymentReference: new PaymentReference('11223344556677'),
            legacyPaymentNumber: null,
            iban: new Iban('GB94BARC10201530093459'),
        );

        $result = $this->commandBus->send($command);
        Assert::isInstanceOf($result->result, PaymentId::class);
        /** @var PaymentId $paymentId */
        $paymentId = $result->result;
        $this->existingCapturedPaymentId = $paymentId;
    }

    #[Given('payment with different details from single_entry_private_debtor.camt.xml already exists')]
    public function paymentWithDifferentDetailsAlreadyExists(): void
    {
        // Create a payment with different details from single_entry_private_debtor.camt.xml
        // This will NOT match the imported payment (low match score)
        $this->existingCapturedPaymentId = PaymentId::generate();

        $command = new CreatePayment(
            paymentId: $this->existingCapturedPaymentId,
            status: PaymentStatus::Captured,
            amount: new Money(25000, new Currency('EUR')), // Different amount: 250.00 EUR
            description: new ShortDescription('Different Donation'),
            gateway: null,
            email: null,
            name: new PersonName('Jane', 'Smith'), // Different name
            nationalIdCode: new NationalIdCode('98765432100'), // Different ID
            paymentAppliedToId: null,
            initiatedAt: new \DateTimeImmutable('2025-11-20'),
            capturedAt: new \DateTimeImmutable('2025-11-20'),
            gatewayTransactionId: null,
            bankReference: null,
            paymentReference: new PaymentReference('99887766554433'), // Different reference
            legacyPaymentNumber: null,
            iban: new Iban('EE382200221020145685'), // Different IBAN
        );

        $result = $this->commandBus->send($command);
        Assert::isInstanceOf($result->result, PaymentId::class);
        /** @var PaymentId $paymentId */
        $paymentId = $result->result;
        $this->existingCapturedPaymentId = $paymentId;
    }

    #[Then('the imported payment is reconciled with existing payment')]
    public function theImportedPaymentIsReconciledWithExistingPayment(): void
    {
        $payment = $this->queryBus->ask(new GetPayment($this->lastPaymentId));
        Assert::isInstanceOf($payment, Payment::class);
        /** @var Payment $payment */
        Assert::eq($payment->getImportStatus(), PaymentImportStatus::Reconciled);
        Assert::notNull($payment->getReconciledWith(), 'Payment should have reconciledWith value');
        Assert::eq(
            $payment->getReconciledWith(),
            $this->existingCapturedPaymentId->toString(),
            'Payment should be reconciled with the existing captured payment'
        );
    }

    #[Then('the imported payment is in review state')]
    public function theImportedPaymentIsInReviewState(): void
    {
        $payment = $this->queryBus->ask(new GetPayment($this->lastPaymentId));
        Assert::isInstanceOf($payment, Payment::class);
        /** @var Payment $payment */
        Assert::eq($payment->getImportStatus(), PaymentImportStatus::Review);
    }

    #[Then('the imported payment is not reconciled with existing payment')]
    public function paymentIsNotReconciledWithExistingPayment(): void
    {
        $payment = $this->queryBus->ask(new GetPayment($this->lastPaymentId));
        Assert::isInstanceOf($payment, Payment::class);
        /** @var Payment $payment */
        Assert::null($payment->getReconciledWith(), 'Payment should not have reconciledWith value');
    }
}
