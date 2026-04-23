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
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPaymentByTrackingId;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPaymentMethodByTrackingId;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\PaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCredentialValue;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportStatus;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUnusableReason;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command\InitiatePaymentIntegrationCommand;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentDidNotSucceedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentMethodUnusableIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentSucceededIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\UnusablePaymentMethodCreatedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\UsablePaymentMethodCreatedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\ValueObject\EntityId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\LegalIdentifier;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\URL;
use ErgoSarapu\DonationBundle\Tests\Acceptance\Payments\FakeGateway;
use Exception;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngine;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Webmozart\Assert\Assert;
use Zenstruck\Messenger\Test\Transport\TestTransport;

class PaymentsContext implements Context
{
    private PaymentId $lastPaymentId;
    private PaymentId $existingCapturedPaymentId;
    private PaymentMethodId $lastPaymentMethodId;
    private string $lastUploadedPaymentImportFile;

    public function __construct(
        private readonly SubscriptionEngine $subscriptionEngine,
        private readonly FakeGateway $gateway,
        #[Autowire(service: 'messenger.transport.integration_command')]
        private readonly TestTransport $integrationCommandTransport,
        #[Autowire(service: 'messenger.transport.integration_event')]
        private readonly TestTransport $integrationEventTransport,
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {
        $this->initProjections();
    }

    #[BeforeScenario]
    public function resetTransports(): void
    {
        $this->integrationCommandTransport->reset();
        $this->integrationEventTransport->reset();
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

    private function dispatchInitiatePaymentCommand(InitiatePaymentIntegrationCommand $command): void
    {
        $this->integrationCommandTransport->reset();
        $trackingId = $this->commandBus->dispatch($command)->trackingId;
        $this->integrationCommandTransport->processOrFail(1);
        $this->resolvePaymentId($trackingId);
    }

    private function resolvePaymentId(string $trackingId): void
    {
        $payment = $this->queryBus->ask(new GetPaymentByTrackingId($trackingId));
        Assert::isInstanceOf($payment, Payment::class, 'Payment should be found for tracking ID: ' . $trackingId);
        /** @var Payment $payment */
        $paymentId = $payment->getPaymentId();
        $this->lastPaymentId = PaymentId::fromString($paymentId);
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

    private function createInitiatePaymentCommand(?PaymentMethodId $paymentMethodId = null, ?EntityId $requestPaymentMethodFor = null): InitiatePaymentIntegrationCommand
    {
        return new InitiatePaymentIntegrationCommand(
            amount: $this->getDefaultTestMoney(),
            gateway: new Gateway('test-gateway'),
            description: new ShortDescription('Test Payment'),
            appliedTo: new EntityId(Uuid::uuid7()->toString()),
            email: new Email('test@example.com'),
            paymentMethodId: $paymentMethodId !== null ? new EntityId($paymentMethodId->toString()) : null,
            requestPaymentMethodFor: $requestPaymentMethodFor,
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
        $this->commandBus->dispatch(new CreatePaymentMethod(
            $this->lastPaymentMethodId,
            PaymentMethodResult::usable(new PaymentCredentialValue('credential-value')),
            Uuid::uuid7()->toString(),
        ));
        $this->integrationEventTransport->reset();
    }

    #[Given('unusable payment method exists')]
    public function unusablePaymentMethodExists(): void
    {
        $this->lastPaymentMethodId = PaymentMethodId::generate();
        $this->commandBus->dispatch(new CreatePaymentMethod(
            $this->lastPaymentMethodId,
            PaymentMethodResult::unusable(PaymentMethodUnusableReason::Expired),
            Uuid::uuid7()->toString(),
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
        $this->commandBus->dispatch(new CreatePaymentMethod(
            PaymentMethodId::generate(),
            PaymentMethodResult::usable(new PaymentCredentialValue('credential-value')),
            Uuid::uuid7()->toString(),
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
        $this->dispatchInitiatePaymentCommand($this->createInitiatePaymentCommand());
    }

    #[When('initiate payment with request to store payment method')]
    public function initiatePaymentWithRequestToStorePaymentMethod(): void
    {
        $this->dispatchInitiatePaymentCommand($this->createInitiatePaymentCommand(requestPaymentMethodFor: new EntityId(Uuid::uuid7()->toString())));
    }

    #[When('initiate payment using stored payment method')]
    public function initiatePaymentUsingStoredPaymentMethod(): void
    {
        $this->dispatchInitiatePaymentCommand($this->createInitiatePaymentCommand($this->lastPaymentMethodId));
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
        $methodResult = $paymentMethodResult ? $this->parsePaymentMethodResult($paymentMethodResult) : null;
        $trackingId = $this->commandBus->dispatch(
            new MarkPaymentAsCaptured(
                $this->lastPaymentId,
                $this->getDefaultTestMoney(),
                $methodResult,
            )
        )->trackingId;
        $this->resolvePaymentMethodId($trackingId);
    }

    #[When('mark payment as failed')]
    public function markPaymentAsFailed(): void
    {
        $this->commandBus->dispatch(
            new MarkPaymentAsFailed(
                $this->lastPaymentId,
                null
            )
        );
    }

    #[When('mark payment as failed with :paymentMethodResult payment method result')]
    public function markPaymentAsFailedWithPaymentMethodResult(?string $paymentMethodResult = null): void
    {
        $result = $paymentMethodResult ? $this->parsePaymentMethodResult($paymentMethodResult) : null;
        $trackingId = $this->commandBus->dispatch(
            new MarkPaymentAsFailed(
                $this->lastPaymentId,
                $result,
            )
        )->trackingId;
        $this->resolvePaymentMethodId($trackingId);
    }

    private function resolvePaymentMethodId(string $trackingId): void
    {
        $paymentMethod = $this->queryBus->ask(new GetPaymentMethodByTrackingId($trackingId));
        Assert::isInstanceOf($paymentMethod, PaymentMethod::class, 'Payment method should be found for tracking ID: ' . $trackingId);
        /** @var PaymentMethod $paymentMethod */
        $paymentMethodId = $paymentMethod->getPaymentMethodId();
        $this->lastPaymentMethodId = PaymentMethodId::fromString($paymentMethodId);
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
        $payment = $this->queryBus->ask(new GetPayment($this->lastPaymentId));
        Assert::isInstanceOf($payment, Payment::class);
        /** @var Payment $payment */
        Assert::eq($payment->getStatus(), PaymentStatus::Initiated);
    }

    #[Then('payment is marked as :paymentState')]
    public function paymentIsMarkedAs(string $paymentState): void
    {
        $payment = $this->queryBus->ask(new GetPayment($this->lastPaymentId));
        Assert::isInstanceOf($payment, Payment::class);
        /** @var Payment $payment */
        Assert::eq($payment->getStatus(), PaymentStatus::from($paymentState));
    }

    #[Then('payment redirect URL is set up')]
    public function paymentRedirectUrlIsSetUp(): void
    {
        $payment = $this->queryBus->ask(new GetPayment($this->lastPaymentId));
        Assert::isInstanceOf($payment, Payment::class);
        /** @var Payment $payment */
        Assert::eq($payment->getRedirectUrl(), 'https://example.com/capture');
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
        $paymentMethod = $this->queryBus->ask(new GetPaymentMethod($this->lastPaymentMethodId));
        Assert::isInstanceOf($paymentMethod, PaymentMethod::class);
        /** @var PaymentMethod $paymentMethod */
        Assert::null($paymentMethod->getUnusableReason());
    }

    #[Then('unusable payment method is created')]
    public function unusablePaymentMethodIsCreated(): void
    {
        $paymentMethod = $this->queryBus->ask(new GetPaymentMethod($this->lastPaymentMethodId));
        Assert::isInstanceOf($paymentMethod, PaymentMethod::class);
        /** @var PaymentMethod $paymentMethod */
        Assert::notNull($paymentMethod->getUnusableReason());
        $this->lastPaymentMethodId = PaymentMethodId::fromString($paymentMethod->getPaymentMethodId());
    }

    #[Then('stored payment method is unusable')]
    public function storedPaymentMethodIsUnusable(): void
    {
        $paymentMethod = $this->queryBus->ask(new GetPaymentMethod($this->lastPaymentMethodId));
        Assert::isInstanceOf($paymentMethod, PaymentMethod::class);
        /** @var PaymentMethod $paymentMethod */
        Assert::notNull($paymentMethod->getUnusableReason());
    }

    #[Then('no payment method integration event is emitted')]
    public function noPaymentMethodIntegrationEventIsEmitted(): void
    {
        $this->integrationEventTransport->queue()->assertNotContains(UsablePaymentMethodCreatedIntegrationEvent::class);
    }

    #[Then('unusable payment method integration event is emitted')]
    public function unusablePaymentMethodIntegrationEventIsEmitted(): void
    {
        $this->integrationEventTransport->queue()->assertContains(PaymentMethodUnusableIntegrationEvent::class);
    }


    #[Then('usable payment method created integration event is emitted')]
    public function usablePaymentMethodCreatedIntegrationEventIsEmitted(): void
    {
        $this->integrationEventTransport->queue()->assertContains(UsablePaymentMethodCreatedIntegrationEvent::class, 1);
    }

    #[Then('unusable payment method created integration event is emitted')]
    public function unusablePaymentMethodCreatedIntegrationEventIsEmitted(): void
    {
        $this->integrationEventTransport->queue()->assertContains(UnusablePaymentMethodCreatedIntegrationEvent::class, 1);
    }

    #[Then('no payment method is stored')]
    public function noPaymentMethodIsStored(): void
    {
        $this->integrationEventTransport->queue()->assertNotContains(UsablePaymentMethodCreatedIntegrationEvent::class);
        $this->integrationEventTransport->queue()->assertNotContains(UnusablePaymentMethodCreatedIntegrationEvent::class);
    }

    #[Given(':fileName has been uploaded')]
    public function paymentImportFileHasBeenUploaded(string $fileName): void
    {
        $this->lastUploadedPaymentImportFile = __DIR__ . '/../../../Unit/Payments/Infrastructure/Fixtures/' . $fileName;
    }

    #[When('import payments from file')]
    public function importPaymentsFromFile(): void
    {
        $commandResult = $this->commandBus->dispatch(new ImportPaymentsFromFile($this->lastUploadedPaymentImportFile));
        Assert::isInstanceOf($commandResult->result, PaymentFileImportResult::class);
        /** @var PaymentFileImportResult $result */
        $result = $commandResult->result;
        Assert::eq(count($result->pendingPaymentIds), 1);
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
            legalIdentifier: LegalIdentifier::nationalIdNumber('60109234794'),
            paymentAppliedToId: null,
            initiatedAt: new \DateTimeImmutable('2025-11-24'),
            capturedAt: new \DateTimeImmutable('2025-11-24'),
            gatewayReference: null,
            bankReference: null,
            paymentReference: new PaymentReference('11223344556677'),
            legacyPaymentNumber: null,
            iban: new Iban('GB94BARC10201530093459'),
        );

        $result = $this->commandBus->dispatch($command);
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
            legalIdentifier: LegalIdentifier::nationalIdNumber('98765432100'), // Different ID
            paymentAppliedToId: null,
            initiatedAt: new \DateTimeImmutable('2025-11-20'),
            capturedAt: new \DateTimeImmutable('2025-11-20'),
            gatewayReference: null,
            bankReference: null,
            paymentReference: new PaymentReference('99887766554433'), // Different reference
            legacyPaymentNumber: null,
            iban: new Iban('EE382200221020145685'), // Different IBAN
        );

        $result = $this->commandBus->dispatch($command);
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
