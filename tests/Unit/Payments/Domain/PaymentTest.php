<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Domain;

use DateInterval;
use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\AccountHolderName;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\BankReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Bic;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\GatewayReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\LegacyPaymentNumber;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentAuthorized;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCanceled;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCaptured;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCreated;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCredentialValue;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentDidNotSucceed;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentFailed;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportAccepted;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportInReview;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportPending;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportReconciled;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportRejected;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportSourceIdentifier;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentInitiated;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodAction;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUnusableReason;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentRedirectUrlSetUp;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentRefunded;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentReleasedForGatewayCall;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentRequest;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentReservedForGatewayCall;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentSucceeded;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ExternalEntityId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\OrganisationRegCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\URL;
use LogicException;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;

class PaymentTest extends AggregateRootTestCase
{
    private DateTimeImmutable $now;

    private PaymentId $paymentId;

    private Money $amount;

    private Gateway $gateway;

    private Email $email;

    private ShortDescription $description;

    private ExternalEntityId $appliedTo;

    protected function aggregateClass(): string
    {
        return Payment::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = new DateTimeImmutable('2024-02-01 00:00:00');
        $this->paymentId = PaymentId::generate();
        $this->amount = new Money(100, new Currency('EUR'));
        $this->gateway = new Gateway('test');
        $this->email = new Email('example@example.com');
        $this->description = new ShortDescription('Test payment');
        $this->appliedTo = ExternalEntityId::generate();
    }

    public function testCreate(): void
    {
        $name = new PersonName('John', 'Doe');
        $nationalIdCode = new NationalIdCode('12345678901');
        $gatewayReference = new GatewayReference('gateway-ref-123');
        $bankReference = new BankReference('bank-ref-456');
        $paymentReference = new PaymentReference('payment-ref-789');
        $legacyPaymentId = @new LegacyPaymentNumber('legacy-789');
        $iban = new Iban('EE382200221020145685');
        $gateway = new Gateway('test-gateway');

        $this->when(fn () => Payment::create(
            $this->now,
            $this->paymentId,
            PaymentStatus::Initiated,
            $this->amount,
            $this->description,
            $gateway,
            $this->appliedTo,
            $this->email,
            $name,
            $nationalIdCode,
            $this->now->sub(new DateInterval('P1D')),
            $this->now->add(new DateInterval('P1D')),
            $gatewayReference,
            $bankReference,
            $paymentReference,
            $legacyPaymentId,
            $iban,
        ))->then(
            new PaymentCreated(
                $this->now,
                $this->now->sub(new DateInterval('P1D')),
                $this->now->add(new DateInterval('P1D')),
                $this->paymentId,
                PaymentStatus::Initiated,
                $this->amount,
                $this->description,
                $gateway,
                $this->appliedTo,
                $this->email,
                $name,
                $nationalIdCode,
                $gatewayReference,
                $bankReference,
                $paymentReference,
                $legacyPaymentId,
                $iban,
            )
        );
    }

    public function testCreatePendingImport(): void
    {
        $sourceIdentifier = new PaymentImportSourceIdentifier('source-123');
        $bankReference = new BankReference('ref-456');
        $bookingDate = $this->now->sub(new DateInterval('P1D'));
        $accountHolderName = new AccountHolderName('John Doe');
        $nationalIdCode = new NationalIdCode('12345678901');
        $organizationRegCode = new OrganisationRegCode('12345678');
        $reference = new PaymentReference('1234567890');
        $iban = new Iban('EE382200221020145685');
        $bic = new Bic('HABAEE2X');

        $this->when(fn () => Payment::createPendingImport(
            $this->now,
            $this->paymentId,
            $sourceIdentifier,
            $bankReference,
            PaymentStatus::Initiated,
            $this->amount,
            $this->description,
            $bookingDate,
            $accountHolderName,
            $nationalIdCode,
            $organizationRegCode,
            $reference,
            $iban,
            $bic,
        ))->then(
            new PaymentImportPending(
                $this->now,
                $this->paymentId,
                $sourceIdentifier,
                $bankReference,
                PaymentStatus::Initiated,
                $this->amount,
                $this->description,
                $bookingDate,
                $accountHolderName,
                $nationalIdCode,
                $organizationRegCode,
                $reference,
                $iban,
                $bic,
            )
        );
    }

    public function testCreatePendingImportWithNullableFields(): void
    {
        $sourceIdentifier = new PaymentImportSourceIdentifier('source-123');
        $bankReference = new BankReference('ref-456');
        $bookingDate = $this->now->sub(new DateInterval('P1D'));

        $this->when(fn () => Payment::createPendingImport(
            $this->now,
            $this->paymentId,
            $sourceIdentifier,
            $bankReference,
            PaymentStatus::Initiated,
            $this->amount,
            null,
            $bookingDate,
            null,
            null,
            null,
            null,
            null,
            null,
        ))->then(
            new PaymentImportPending(
                $this->now,
                $this->paymentId,
                $sourceIdentifier,
                $bankReference,
                PaymentStatus::Initiated,
                $this->amount,
                null,
                $bookingDate,
                null,
                null,
                null,
                null,
                null,
                null,
            )
        );
    }

    public function testInitiate(): void
    {
        $paymentRequest = new PaymentRequest(
            $this->paymentId,
            $this->amount,
            $this->gateway,
            $this->description,
            $this->appliedTo,
            $this->email,
            null,
        );
        $this->when(fn () => Payment::initiate(
            $this->now,
            $paymentRequest,
        ))->then(
            new PaymentInitiated(
                $this->now,
                $this->paymentId,
                $this->amount,
                $this->gateway,
                $this->description,
                $this->appliedTo,
                $this->email,
                null,
            )
        );
    }

    public function testInitiateWithPaymentMethodAction(): void
    {
        $methodAction = PaymentMethodAction::forRequest(
            PaymentMethodId::generate(),
            $this->paymentId,
            ExternalEntityId::generate(),
        );
        $paymentRequest = new PaymentRequest(
            $this->paymentId,
            $this->amount,
            $this->gateway,
            $this->description,
            $this->appliedTo,
            $this->email,
            $methodAction,
        );
        $this->when(fn () => Payment::initiate(
            $this->now,
            $paymentRequest,
        ))->then(
            new PaymentInitiated(
                $this->now,
                $this->paymentId,
                $this->amount,
                $this->gateway,
                $this->description,
                $this->appliedTo,
                $this->email,
                $methodAction,
            )
        );
    }

    public function testInitiatePaymentMethodActionPaymentIdMismatchThrows(): void
    {
        $methodAction = PaymentMethodAction::forRequest(
            PaymentMethodId::generate(),
            PaymentId::generate(),
            ExternalEntityId::generate(),
        );
        $paymentRequest = new PaymentRequest(
            $this->paymentId,
            $this->amount,
            $this->gateway,
            $this->description,
            $this->appliedTo,
            $this->email,
            $methodAction,
        );
        $this->when(fn () => Payment::initiate(
            $this->now,
            $paymentRequest,
        ))->expectsException(LogicException::class)->expectsExceptionMessage('Payment method action paymentId does not match payment request paymentId.');
    }

    public function testMarkCapturedOnInitiated(): void
    {
        $methodAction = PaymentMethodAction::forRequest(
            PaymentMethodId::generate(),
            $this->paymentId,
            ExternalEntityId::generate(),
        );
        $methodResult = PaymentMethodResult::usable(new PaymentCredentialValue('token'));
        $this->given(new PaymentInitiated(
            $this->now,
            $this->paymentId,
            $this->amount,
            $this->gateway,
            $this->description,
            $this->appliedTo,
            $this->email,
            $methodAction,
        ))
        ->when(fn (Payment $payment) => $payment->markCaptured(
            $this->now,
            $this->amount,
            $methodResult,
        ))
        ->then(
            new PaymentCaptured(
                $this->now,
                $this->paymentId,
                $this->amount,
                $this->appliedTo,
                $methodAction,
                $methodResult,
            ),
            new PaymentSucceeded(
                $this->now,
                $this->paymentId,
                $this->amount,
                $this->appliedTo,
            )
        );
    }

    public function testMarkCapturedOnAuthorized(): void
    {
        $methodAction = PaymentMethodAction::forRequest(
            PaymentMethodId::generate(),
            $this->paymentId,
            ExternalEntityId::generate(),
        );
        $methodResult = PaymentMethodResult::usable(new PaymentCredentialValue('token'));
        $this->given(new PaymentInitiated(
            $this->now,
            $this->paymentId,
            $this->amount,
            $this->gateway,
            $this->description,
            $this->appliedTo,
            $this->email,
            $methodAction,
        ), new PaymentAuthorized(
            $this->now,
            $this->paymentId,
            $this->amount,
            $this->appliedTo,
            $methodAction,
            $methodResult,
        ), new PaymentSucceeded(
            $this->now,
            $this->paymentId,
            $this->amount,
            $this->appliedTo,
        ))
        ->when(fn (Payment $payment) => $payment->markCaptured(
            $this->now,
            $this->amount,
            $methodResult,
        ))
        ->then(
            new PaymentCaptured(
                $this->now,
                $this->paymentId,
                $this->amount,
                $this->appliedTo,
                $methodAction,
                $methodResult,
            )
        );
    }
    public function testMarkCapturedIdempotent(): void
    {
        $this->given(new PaymentCaptured(
            $this->now,
            $this->paymentId,
            $this->amount,
            $this->appliedTo,
        ))->when(fn (Payment $payment) => $payment->markCaptured(
            $this->now,
            $this->amount,
            null,
        ))->then();
    }

    public function testMarkCapturedOnCanceledThrows(): void
    {
        $this->given(new PaymentCanceled(
            $this->now,
            $this->paymentId,
        ))->when(fn (Payment $payment) => $payment->markCaptured(
            $this->now,
            $this->amount,
            null,
        ))->expectsException(LogicException::class)->expectsExceptionMessage('Cannot transition from canceled to captured.');
    }

    public function testMarkCapturedOnFailedThrows(): void
    {
        $this->given(new PaymentFailed(
            $this->now,
            $this->paymentId,
        ))->when(fn (Payment $payment) => $payment->markCaptured(
            $this->now,
            $this->amount,
            null,
        ))->expectsException(LogicException::class)->expectsExceptionMessage('Cannot transition from failed to captured.');
    }

    public function testMarkCapturedOnRefundedThrows(): void
    {
        $this->given(new PaymentRefunded(
            $this->now,
            $this->paymentId,
            new Money(0, new Currency('EUR')),
        ))->when(fn (Payment $payment) => $payment->markCaptured(
            $this->now,
            $this->amount,
            null,
        ))->expectsException(LogicException::class)->expectsExceptionMessage('Cannot transition from refunded to captured.');
    }

    public function testMarkAuthoriszedOnInitiated(): void
    {
        $methodAction = PaymentMethodAction::forRequest(
            PaymentMethodId::generate(),
            $this->paymentId,
            ExternalEntityId::generate(),
        );
        $methodResult = PaymentMethodResult::usable(new PaymentCredentialValue('token'));
        $this->given(new PaymentInitiated(
            $this->now,
            $this->paymentId,
            $this->amount,
            $this->gateway,
            $this->description,
            $this->appliedTo,
            $this->email,
            $methodAction,
        ))
        ->when(fn (Payment $payment) => $payment->markAuthorized(
            $this->now,
            $this->amount,
            $methodResult,
        ))
        ->then(
            new PaymentAuthorized(
                $this->now,
                $this->paymentId,
                $this->amount,
                $this->appliedTo,
                $methodAction,
                $methodResult,
            ),
            new PaymentSucceeded(
                $this->now,
                $this->paymentId,
                $this->amount,
                $this->appliedTo,
            )
        );
    }

    public function testMarkAuthorizedIdempotent(): void
    {
        $this->given(new PaymentAuthorized(
            $this->now,
            $this->paymentId,
            $this->amount,
        ))->when(fn (Payment $payment) => $payment->markAuthorized(
            $this->now,
            $this->amount,
            null,
        ))->then();
    }

    public function testMarkAuthorizedOnFailedThrows(): void
    {
        $this->given(new PaymentFailed(
            $this->now,
            $this->paymentId,
        ))->when(fn (Payment $payment) => $payment->markAuthorized(
            $this->now,
            $this->amount,
            null,
        ))->expectsException(LogicException::class)->expectsExceptionMessage('Cannot transition from failed to authorized.');
    }

    public function testMarkAuthorizedOnCapturedThrows(): void
    {
        $this->given(new PaymentCaptured(
            $this->now,
            $this->paymentId,
            $this->amount,
            $this->appliedTo,
        ))->when(fn (Payment $payment) => $payment->markAuthorized(
            $this->now,
            $this->amount,
            null,
        ))->expectsException(LogicException::class)->expectsExceptionMessage('Cannot transition from captured to authorized.');
    }

    public function testMarkAuthorizedOnRefundedThrows(): void
    {
        $this->given(new PaymentRefunded(
            $this->now,
            $this->paymentId,
            new Money(0, new Currency('EUR')),
        ))->when(fn (Payment $payment) => $payment->markAuthorized(
            $this->now,
            $this->amount,
            null,
        ))->expectsException(LogicException::class)->expectsExceptionMessage('Cannot transition from refunded to authorized.');
    }

    public function testMarkFailedOnInitiated(): void
    {
        $methodAction = PaymentMethodAction::forRequest(
            PaymentMethodId::generate(),
            $this->paymentId,
            ExternalEntityId::generate(),
        );
        $methodResult = PaymentMethodResult::unusable(PaymentMethodUnusableReason::RequestFailed);
        $this->given(new PaymentInitiated(
            $this->now,
            $this->paymentId,
            $this->amount,
            $this->gateway,
            $this->description,
            $this->appliedTo,
            $this->email,
            $methodAction,
        ))
        ->when(fn (Payment $payment) => $payment->markFailed(
            $this->now,
            $methodResult,
        ))
        ->then(
            new PaymentFailed(
                $this->now,
                $this->paymentId,
                $this->appliedTo,
                $methodAction,
                $methodResult,
            ),
            new PaymentDidNotSucceed(
                $this->now,
                $this->paymentId,
                $this->appliedTo,
            )
        );
    }

    public function testMarkFailedOnAuthorized(): void
    {
        $this->given(new PaymentInitiated(
            $this->now,
            $this->paymentId,
            $this->amount,
            $this->gateway,
            $this->description,
            $this->appliedTo,
            $this->email,
            null,
        ), new PaymentAuthorized(
            $this->now,
            $this->paymentId,
            $this->amount,
            $this->appliedTo,
        ))->when(fn (Payment $payment) => $payment->markFailed(
            $this->now,
            null,
        ))->then(
            new PaymentFailed(
                $this->now,
                $this->paymentId,
                $this->appliedTo,
                null,
                null,
            ),
            new PaymentDidNotSucceed(
                $this->now,
                $this->paymentId,
                $this->appliedTo,
            )
        );
    }

    public function testMarkFailedIdempotent(): void
    {
        $this->given(new PaymentFailed(
            $this->now,
            $this->paymentId,
            $this->appliedTo,
        ))->when(fn (Payment $payment) => $payment->markFailed(
            $this->now,
            null,
        ))->then();
    }

    public function testMarkFailedOnCanceledThrows(): void
    {
        $this->given(new PaymentCanceled(
            $this->now,
            $this->paymentId,
        ))->when(fn (Payment $payment) => $payment->markFailed(
            $this->now,
            null,
        ))->expectsException(LogicException::class)->expectsExceptionMessage('Cannot transition from canceled to failed.');
    }

    public function testMarkFailedOnCapturedThrows(): void
    {
        $this->given(new PaymentCaptured(
            $this->now,
            $this->paymentId,
            $this->amount,
            $this->appliedTo,
        ))->when(fn (Payment $payment) => $payment->markFailed(
            $this->now,
            null,
        ))->expectsException(LogicException::class)->expectsExceptionMessage('Cannot transition from captured to failed.');
    }

    public function testMarkFailedOnRefundedThrows(): void
    {
        $this->given(new PaymentRefunded(
            $this->now,
            $this->paymentId,
            new Money(0, new Currency('EUR')),
        ))->when(fn (Payment $payment) => $payment->markFailed(
            $this->now,
            null,
        ))->expectsException(LogicException::class)->expectsExceptionMessage('Cannot transition from refunded to failed.');
    }

    public function testMarkCanceledOnInitiated(): void
    {
        $methodAction = PaymentMethodAction::forRequest(
            PaymentMethodId::generate(),
            $this->paymentId,
            ExternalEntityId::generate(),
        );
        $this->given(new PaymentInitiated(
            $this->now,
            $this->paymentId,
            $this->amount,
            $this->gateway,
            $this->description,
            $this->appliedTo,
            $this->email,
            $methodAction,
        ))
        ->when(fn (Payment $payment) => $payment->markCanceled(
            $this->now,
        ))
        ->then(
            new PaymentCanceled(
                $this->now,
                $this->paymentId,
            ),
            new PaymentDidNotSucceed(
                $this->now,
                $this->paymentId,
                $this->appliedTo,
            )
        );
    }

    public function testMarkCanceledIdempotent(): void
    {
        $this->given(new PaymentCanceled(
            $this->now,
            $this->paymentId,
        ))->when(fn (Payment $payment) => $payment->markCanceled(
            $this->now,
        ))->then();
    }
    public function testMarkCanceleddOnFailedThrows(): void
    {
        $this->given(new PaymentFailed(
            $this->now,
            $this->paymentId,
        ))->when(fn (Payment $payment) => $payment->markCanceled(
            $this->now,
        ))->expectsException(LogicException::class)->expectsExceptionMessage('Cannot transition from failed to canceled.');
    }

    public function testMarkCanceledOnAuthorizedThrows(): void
    {
        $this->given(
            new PaymentInitiated(
                $this->now,
                $this->paymentId,
                $this->amount,
                $this->gateway,
                $this->description,
                $this->appliedTo,
                $this->email,
            ),
            new PaymentAuthorized(
                $this->now,
                $this->paymentId,
                $this->amount,
                $this->appliedTo,
            )
        )->when(fn (Payment $payment) => $payment->markCanceled(
            $this->now,
        ))->expectsException(LogicException::class)->expectsExceptionMessage('Cannot transition from authorized to canceled.');
    }

    public function testMarkCanceledOnCapturedThrows(): void
    {
        $this->given(
            new PaymentCaptured(
                $this->now,
                $this->paymentId,
                $this->amount,
                $this->appliedTo,
            )
        )->when(fn (Payment $payment) => $payment->markCanceled(
            $this->now,
        ))->expectsException(LogicException::class)->expectsExceptionMessage('Cannot transition from captured to canceled.');
    }

    public function testMarkCanceledOnRefundedThrows(): void
    {
        $this->given(
            new PaymentRefunded(
                $this->now,
                $this->paymentId,
                new Money(0, new Currency('EUR')),
            )
        )->when(fn (Payment $payment) => $payment->markCanceled(
            $this->now,
        ))->expectsException(LogicException::class)->expectsExceptionMessage('Cannot transition from refunded to canceled.');
    }

    public function testMarkRefundedOnCaptured(): void
    {
        $this->given(
            new PaymentInitiated(
                $this->now,
                $this->paymentId,
                $this->amount,
                $this->gateway,
                $this->description,
                $this->appliedTo,
                $this->email,
                PaymentMethodAction::forRequest(
                    PaymentMethodId::generate(),
                    $this->paymentId,
                    ExternalEntityId::generate(),
                ),
            ),
            new PaymentCaptured(
                $this->now,
                $this->paymentId,
                $this->amount,
                $this->appliedTo,
            )
        )
        ->when(fn (Payment $payment) => $payment->markRefunded(
            $this->now,
            $this->amount,
        ))
        ->then(
            new PaymentRefunded(
                $this->now,
                $this->paymentId,
                $this->amount,
            )
        );
    }

    public function testMarkRefundedIdempotent(): void
    {
        $this->given(new PaymentRefunded(
            $this->now,
            $this->paymentId,
            $this->amount,
        ))->when(fn (Payment $payment) => $payment->markRefunded(
            $this->now,
            $this->amount,
        ))->then();
    }

    public function testMarkRefudedOnInitiatedThrows(): void
    {
        $this->given(
            new PaymentInitiated(
                $this->now,
                $this->paymentId,
                $this->amount,
                $this->gateway,
                $this->description,
                $this->appliedTo,
                $this->email,
            )
        )->when(fn (Payment $payment) => $payment->markRefunded(
            $this->now,
            new Money(0, new Currency('EUR')),
        ))->expectsException(LogicException::class)->expectsExceptionMessage('Cannot transition from initiated to refunded.');
    }

    public function testMarkRefudedOnCanceledThrows(): void
    {
        $this->given(
            new PaymentCanceled(
                $this->now,
                $this->paymentId,
            )
        )->when(fn (Payment $payment) => $payment->markRefunded(
            $this->now,
            new Money(0, new Currency('EUR')),
        ))->expectsException(LogicException::class)->expectsExceptionMessage('Cannot transition from canceled to refunded.');
    }

    public function testMarkRefudedOnFailedThrows(): void
    {
        $this->given(
            new PaymentFailed(
                $this->now,
                $this->paymentId,
            )
        )->when(fn (Payment $payment) => $payment->markRefunded(
            $this->now,
            new Money(0, new Currency('EUR')),
        ))->expectsException(LogicException::class)->expectsExceptionMessage('Cannot transition from failed to refunded.');
    }

    public function testMarkRefudedOnAuthorizedThrows(): void
    {
        $this->given(
            new PaymentAuthorized(
                $this->now,
                $this->paymentId,
                $this->amount,
                $this->appliedTo,
            )
        )->when(fn (Payment $payment) => $payment->markRefunded(
            $this->now,
            new Money(0, new Currency('EUR')),
        ))->expectsException(LogicException::class)->expectsExceptionMessage('Cannot transition from authorized to refunded.');
    }

    public function testSucceededRecordedOnce(): void
    {
        $this->given(
            new PaymentInitiated(
                $this->now,
                $this->paymentId,
                $this->amount,
                $this->gateway,
                $this->description,
                $this->appliedTo,
                $this->email,
                null,
            ),
            new PaymentAuthorized(
                $this->now,
                $this->paymentId,
                $this->amount,
                $this->appliedTo,
                null,
                null,
            ),
            new PaymentSucceeded(
                $this->now,
                $this->paymentId,
                $this->amount,
                $this->appliedTo,
            )
        )
        ->when(fn (Payment $payment) => $payment->markCaptured(
            $this->now,
            $this->amount,
            null,
        ))
        ->then(
            new PaymentCaptured(
                $this->now,
                $this->paymentId,
                $this->amount,
                $this->appliedTo,
                null,
                null,
            )
        );
    }

    public function testReserveGatewayCall(): void
    {
        $this->given(new PaymentInitiated(
            $this->now,
            $this->paymentId,
            $this->amount,
            $this->gateway,
            $this->description,
            $this->appliedTo,
            $this->email,
            null,
        ))
        ->when(function (Payment $payment) {
            $request = $payment->reserveGatewayCall(
                $this->now,
            );
            $this->assertNotNull($request);
            $this->assertEquals($this->paymentId, $request->paymentId);
            $this->assertEquals($this->amount, $request->amount);
            $this->assertEquals($this->gateway, $request->gateway);
            $this->assertEquals($this->description, $request->description);
            $this->assertEquals($this->email, $request->email);
        })
        ->then(
            new PaymentReservedForGatewayCall(
                $this->now,
                $this->paymentId,
            )
        );
    }

    public function testReserveGatewayCallAlreadyReserved(): void
    {
        $this->given(new PaymentReservedForGatewayCall(
            $this->now,
            $this->paymentId,
        ))
        ->when(
            function (Payment $payment) {
                $request = $payment->reserveGatewayCall(
                    $this->now,
                );
                $this->assertNull($request);
            }
        )->then();
    }

    public function testReserveGatewayCallThrowsWhenNotInitiated(): void
    {
        $this->given(new PaymentAuthorized(
            $this->now,
            $this->paymentId,
            $this->amount,
            $this->appliedTo,
        ))
        ->when(fn (Payment $payment) => $payment->reserveGatewayCall(
            $this->now,
        ))
        ->expectsException(LogicException::class)->expectsExceptionMessage('Can only initiate gateway call for initiated payment.');
    }

    public function testReleaseGatewayCall(): void
    {
        $this->given(new PaymentInitiated(
            $this->now,
            $this->paymentId,
            $this->amount,
            $this->gateway,
            $this->description,
            $this->appliedTo,
            $this->email,
            null,
        ), new PaymentReservedForGatewayCall(
            $this->now,
            $this->paymentId,
        ))
        ->when(function (Payment $payment) {
            $payment->releaseGatewayCall(
                $this->now,
            );
        })
        ->then(
            new PaymentReleasedForGatewayCall(
                $this->now,
                $this->paymentId,
            )
        );
    }

    public function testReleaseGatewayCallIdempotent(): void
    {
        $this->given(new PaymentReleasedForGatewayCall(
            $this->now,
            $this->paymentId,
        ))
        ->when(function (Payment $payment) {
            $payment->releaseGatewayCall(
                $this->now,
            );
        })
        ->then();
    }

    public function testSetRedirectURL(): void
    {
        $this->given(new PaymentInitiated(
            $this->now,
            $this->paymentId,
            $this->amount,
            $this->gateway,
            $this->description,
            $this->appliedTo,
            $this->email,
            null,
        ))
        ->when(fn (Payment $payment) => $payment->setRedirectURL(
            $this->now,
            new URL('https://example.com/redirect'),
        ))
        ->then(
            new PaymentRedirectUrlSetUp(
                $this->now,
                $this->paymentId,
                new URL('https://example.com/redirect'),
            )
        );
    }

    public function testAcceptImport(): void
    {
        $this->given(new PaymentImportInReview(
            $this->now,
            $this->paymentId,
        ))
        ->when(fn (Payment $payment) => $payment->acceptImport($this->now))
        ->then(
            new PaymentImportAccepted(
                $this->now,
                $this->paymentId,
            )
        );
    }

    public function testAcceptImportIdempotent(): void
    {
        $this->given(new PaymentImportAccepted(
            $this->now,
            $this->paymentId,
        ))
        ->when(fn (Payment $payment) => $payment->acceptImport($this->now))
        ->then();
    }

    public function testAcceptImportOnPendingThrows(): void
    {
        $sourceIdentifier = new PaymentImportSourceIdentifier('source-123');
        $this->given(new PaymentImportPending(
            $this->now,
            $this->paymentId,
            $sourceIdentifier,
            null,
            PaymentStatus::Settled,
            $this->amount,
            $this->description,
            $this->now,
            null,
            null,
            null,
            null,
            null,
            null,
        ))
        ->when(fn (Payment $payment) => $payment->acceptImport($this->now))
        ->expectsException(LogicException::class)
        ->expectsExceptionMessage('Can only accept payment import in review.');
    }

    public function testAcceptImportOnRejectedThrows(): void
    {
        $this->given(new PaymentImportRejected(
            $this->now,
            $this->paymentId,
        ))
        ->when(fn (Payment $payment) => $payment->acceptImport($this->now))
        ->expectsException(LogicException::class)
        ->expectsExceptionMessage('Can only accept payment import in review.');
    }

    public function testAcceptImportOnReconciledThrows(): void
    {
        $this->given(new PaymentImportReconciled(
            $this->now,
            $this->paymentId,
            PaymentId::generate(),
        ))
        ->when(fn (Payment $payment) => $payment->acceptImport($this->now))
        ->expectsException(LogicException::class)
        ->expectsExceptionMessage('Can only accept payment import in review.');
    }

    public function testRejectImport(): void
    {
        $this->given(new PaymentImportInReview(
            $this->now,
            $this->paymentId,
        ))
        ->when(fn (Payment $payment) => $payment->rejectImport($this->now))
        ->then(
            new PaymentImportRejected(
                $this->now,
                $this->paymentId,
            )
        );
    }

    public function testRejectImportIdempotent(): void
    {
        $this->given(new PaymentImportRejected(
            $this->now,
            $this->paymentId,
        ))
        ->when(fn (Payment $payment) => $payment->rejectImport($this->now))
        ->then();
    }

    public function testRejectImportOnPendingThrows(): void
    {
        $sourceIdentifier = new PaymentImportSourceIdentifier('source-123');
        $this->given(new PaymentImportPending(
            $this->now,
            $this->paymentId,
            $sourceIdentifier,
            null,
            PaymentStatus::Settled,
            $this->amount,
            $this->description,
            $this->now,
            null,
            null,
            null,
            null,
            null,
            null,
        ))
        ->when(fn (Payment $payment) => $payment->rejectImport($this->now))
        ->expectsException(LogicException::class)
        ->expectsExceptionMessage('Can only reject payment import in review.');
    }

    public function testRejectImportOnAcceptedThrows(): void
    {
        $this->given(new PaymentImportAccepted(
            $this->now,
            $this->paymentId,
        ))
        ->when(fn (Payment $payment) => $payment->rejectImport($this->now))
        ->expectsException(LogicException::class)
        ->expectsExceptionMessage('Can only reject payment import in review.');
    }

    public function testRejectImportOnReconciledThrows(): void
    {
        $this->given(new PaymentImportReconciled(
            $this->now,
            $this->paymentId,
            PaymentId::generate(),
        ))
        ->when(fn (Payment $payment) => $payment->rejectImport($this->now))
        ->expectsException(LogicException::class)
        ->expectsExceptionMessage('Can only reject payment import in review.');
    }

    public function testMoveImportToReview(): void
    {
        $sourceIdentifier = new PaymentImportSourceIdentifier('source-123');
        $this->given(new PaymentImportPending(
            $this->now,
            $this->paymentId,
            $sourceIdentifier,
            null,
            PaymentStatus::Settled,
            $this->amount,
            $this->description,
            $this->now,
            null,
            null,
            null,
            null,
            null,
            null,
        ))
        ->when(fn (Payment $payment) => $payment->moveToReview($this->now))
        ->then(
            new PaymentImportInReview(
                $this->now,
                $this->paymentId,
            )
        );
    }

    public function testMoveImportToReviewIdempotent(): void
    {
        $this->given(new PaymentImportInReview(
            $this->now,
            $this->paymentId,
        ))
        ->when(fn (Payment $payment) => $payment->moveToReview($this->now))
        ->then();
    }

    public function testMoveImportToReviewThrowsWhenNotPendingImport(): void
    {
        $this->given(new PaymentInitiated(
            $this->now,
            $this->paymentId,
            $this->amount,
            $this->gateway,
            $this->description,
            $this->appliedTo,
            $this->email,
            null,
        ))
        ->when(fn (Payment $payment) => $payment->moveToReview($this->now))
        ->expectsException(LogicException::class)
        ->expectsExceptionMessage('Can only move pending imported payments to review.');
    }

    public function testReconcilePendingImport(): void
    {
        $importedPaymentId = PaymentId::generate();
        $existingPaymentId = PaymentId::generate();
        $sourceIdentifier = new PaymentImportSourceIdentifier('source-123');

        $name = new PersonName('Jane', 'Doe');
        $nationalIdCode = new NationalIdCode('12345678901');
        $gatewayReference = new GatewayReference('proc-ref-123');
        $bankReference = new BankReference('bank-ref-456');
        $paymentReference = new PaymentReference('payment-ref-789');
        $legacyPaymentId = @new LegacyPaymentNumber('legacy-789');
        $iban = new Iban('EE382200221020145685');

        $existingPayment = Payment::create(
            $this->now,
            $existingPaymentId,
            PaymentStatus::Captured,
            $this->amount,
            $this->description,
            $this->gateway,
            $this->appliedTo,
            $this->email,
            $name,
            $nationalIdCode,
            $this->now->sub(new DateInterval('P1D')),
            $this->now->add(new DateInterval('P1D')),
            $gatewayReference,
            $bankReference,
            $paymentReference,
            $legacyPaymentId,
            $iban,
        );

        $this->given(new PaymentImportPending(
            $this->now,
            $importedPaymentId,
            $sourceIdentifier,
            null,
            PaymentStatus::Settled,
            $this->amount,
            $this->description,
            $this->now,
            null,
            null,
            null,
            null,
            null,
            null,
        ))
        ->when(fn (Payment $payment) => $payment->reconcileImport($this->now, $existingPayment))
        ->then(
            new PaymentImportReconciled(
                $this->now,
                $importedPaymentId,
                $existingPaymentId,
            )
        );
    }

    public function testReconcileInReviewImport(): void
    {
        $importedPaymentId = PaymentId::generate();
        $existingPaymentId = PaymentId::generate();
        $sourceIdentifier = new PaymentImportSourceIdentifier('source-123');

        $name = new PersonName('Jane', 'Doe');
        $nationalIdCode = new NationalIdCode('12345678901');
        $gatewayReference = new GatewayReference('proc-ref-123');
        $bankReference = new BankReference('bank-ref-456');
        $paymentReference = new PaymentReference('payment-ref-789');
        $legacyPaymentId = @new LegacyPaymentNumber('legacy-789');
        $iban = new Iban('EE382200221020145685');

        $existingPayment = Payment::create(
            $this->now,
            $existingPaymentId,
            PaymentStatus::Captured,
            $this->amount,
            $this->description,
            $this->gateway,
            $this->appliedTo,
            $this->email,
            $name,
            $nationalIdCode,
            $this->now->sub(new DateInterval('P1D')),
            $this->now->add(new DateInterval('P1D')),
            $gatewayReference,
            $bankReference,
            $paymentReference,
            $legacyPaymentId,
            $iban,
        );

        $this->given(
            new PaymentImportPending(
                $this->now,
                $importedPaymentId,
                $sourceIdentifier,
                null,
                PaymentStatus::Settled,
                $this->amount,
                $this->description,
                $this->now,
                null,
                null,
                null,
                null,
                null,
                null,
            ),
            new PaymentImportInReview(
                $this->now,
                $importedPaymentId,
            )
        )
        ->when(fn (Payment $payment) => $payment->reconcileImport($this->now, $existingPayment))
        ->then(
            new PaymentImportReconciled(
                $this->now,
                $importedPaymentId,
                $existingPaymentId,
            )
        );
    }

    public function testReconcileImportWithSelfThrows(): void
    {
        $sourceIdentifier = new PaymentImportSourceIdentifier('source-123');

        $importedPayment = Payment::createPendingImport(
            $this->now,
            $this->paymentId,
            $sourceIdentifier,
            null,
            PaymentStatus::Captured,
            $this->amount,
            $this->description,
            $this->now,
            null,
            null,
            null,
            null,
            null,
            null,
        );

        $this->given(new PaymentImportPending(
            $this->now,
            $this->paymentId,
            $sourceIdentifier,
            null,
            PaymentStatus::Captured,
            $this->amount,
            $this->description,
            $this->now,
            null,
            null,
            null,
            null,
            null,
            null,
        ))
        ->when(fn (Payment $payment) => $payment->reconcileImport($this->now, $importedPayment))
        ->expectsException(LogicException::class)
        ->expectsExceptionMessage('Cannot reconcile payment with itself.');
    }

    public function testReconcileImportWithDifferentAmountThrows(): void
    {
        $importedPaymentId = PaymentId::generate();
        $existingPaymentId = PaymentId::generate();
        $sourceIdentifier = new PaymentImportSourceIdentifier('source-123');
        $differentAmount = new Money(200, new Currency('EUR'));
        $existingPayment = Payment::create(
            $this->now,
            $existingPaymentId,
            PaymentStatus::Captured,
            $differentAmount,
            $this->description,
            $this->gateway,
            $this->appliedTo,
            $this->email,
            null,
            null,
            $this->now->sub(new DateInterval('P1D')),
            $this->now->add(new DateInterval('P1D')),
            null,
            null,
            null,
            null,
            null,
        );

        $this->given(new PaymentImportPending(
            $this->now,
            $importedPaymentId,
            $sourceIdentifier,
            null,
            PaymentStatus::Captured,
            $this->amount,
            $this->description,
            $this->now,
            null,
            null,
            null,
            null,
            null,
            null,
        ))
        ->when(fn (Payment $payment) => $payment->reconcileImport($this->now, $existingPayment))
        ->expectsException(LogicException::class)
        ->expectsExceptionMessage('Cannot reconcile payments with differences in amount.');
    }

    public function testReconcileRejectedImportThrows(): void
    {
        $existingPaymentId = PaymentId::generate();
        $existingPayment = Payment::create(
            $this->now,
            $existingPaymentId,
            PaymentStatus::Captured,
            $this->amount,
            $this->description,
            $this->gateway,
            $this->appliedTo,
            $this->email,
            null,
            null,
            $this->now->sub(new DateInterval('P1D')),
            $this->now->add(new DateInterval('P1D')),
            null,
            null,
            null,
            null,
            null,
        );

        $this->given(
            new PaymentImportPending(
                $this->now,
                $this->paymentId,
                new PaymentImportSourceIdentifier('source-123'),
                null,
                PaymentStatus::Captured,
                $this->amount,
                $this->description,
                $this->now,
                null,
                null,
                null,
                null,
                null,
                null,
            ),
            new PaymentImportRejected(
                $this->now,
                $this->paymentId,
            )
        )
        ->when(fn (Payment $payment) => $payment->reconcileImport($this->now, $existingPayment))
        ->expectsException(LogicException::class)
        ->expectsExceptionMessage('Can only reconcile pending or review imported payments.');
    }

    public function testReconcileAcceptedImportThrows(): void
    {
        $existingPaymentId = PaymentId::generate();
        $existingPayment = Payment::create(
            $this->now,
            $existingPaymentId,
            PaymentStatus::Captured,
            $this->amount,
            $this->description,
            $this->gateway,
            $this->appliedTo,
            $this->email,
            null,
            null,
            $this->now->sub(new DateInterval('P1D')),
            $this->now->add(new DateInterval('P1D')),
            null,
            null,
            null,
            null,
            null,
        );

        $this->given(
            new PaymentImportPending(
                $this->now,
                $this->paymentId,
                new PaymentImportSourceIdentifier('source-123'),
                null,
                PaymentStatus::Captured,
                $this->amount,
                $this->description,
                $this->now,
                null,
                null,
                null,
                null,
                null,
                null,
            ),
            new PaymentImportAccepted(
                $this->now,
                $this->paymentId,
            )
        )
        ->when(fn (Payment $payment) => $payment->reconcileImport($this->now, $existingPayment))
        ->expectsException(LogicException::class)
        ->expectsExceptionMessage('Can only reconcile pending or review imported payments.');
    }

    public function testReconcileNonImportImportThrows(): void
    {
        $existingPaymentId = PaymentId::generate();
        $existingPayment = Payment::create(
            $this->now,
            $existingPaymentId,
            PaymentStatus::Captured,
            $this->amount,
            $this->description,
            $this->gateway,
            $this->appliedTo,
            $this->email,
            null,
            null,
            $this->now->sub(new DateInterval('P1D')),
            $this->now->add(new DateInterval('P1D')),
            null,
            null,
            null,
            null,
            null,
        );

        $this->given(new PaymentInitiated(
            $this->now,
            $this->paymentId,
            $this->amount,
            $this->gateway,
            $this->description,
            $this->appliedTo,
            $this->email,
            null,
        ))
        ->when(fn (Payment $payment) => $payment->reconcileImport($this->now, $existingPayment))
        ->expectsException(LogicException::class)
        ->expectsExceptionMessage('Can only reconcile pending or review imported payments.');
    }
}
