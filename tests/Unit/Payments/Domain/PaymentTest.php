<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Domain;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentAuthorized;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCanceled;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCaptured;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCredentialValue;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentDidNotSucceed;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentFailed;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentInitiated;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodAction;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUnusableReason;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentRedirectUrlSetUp;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentRefunded;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentReleasedForGatewayCall;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentRequest;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentReservedForGatewayCall;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentSucceeded;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodlId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
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

    private PaymentAppliedToId $appliedTo;

    private PaymentMethodlId $paymentMethodId;

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
        $this->appliedTo = PaymentAppliedToId::generate();
        $this->paymentMethodId = PaymentMethodlId::generate();
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
        );
        $this->when(fn () => Payment::initiate(
            $this->now,
            $paymentRequest,
            null,
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
        $paymentRequest = new PaymentRequest(
            $this->paymentId,
            $this->amount,
            $this->gateway,
            $this->description,
            $this->appliedTo,
            $this->email,
        );
        $methodAction = PaymentMethodAction::forRequest(
            $this->paymentMethodId,
            $this->paymentId,
        );
        $this->when(fn () => Payment::initiate(
            $this->now,
            $paymentRequest,
            $methodAction,
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
        $paymentRequest = new PaymentRequest(
            $this->paymentId,
            $this->amount,
            $this->gateway,
            $this->description,
            $this->appliedTo,
            $this->email,
        );
        $methodAction = PaymentMethodAction::forRequest(
            $this->paymentMethodId,
            PaymentId::generate(),
        );
        $this->when(fn () => Payment::initiate(
            $this->now,
            $paymentRequest,
            $methodAction,
        ))->expectsException(LogicException::class)->expectsExceptionMessage('Payment method action paymentId does not match payment request paymentId.');
    }

    public function testMarkCapturedOnInitiated(): void
    {
        $methodAction = PaymentMethodAction::forRequest(
            $this->paymentMethodId,
            $this->paymentId,
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
            $this->paymentMethodId,
            $this->paymentId,
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
            $this->paymentMethodId,
            $this->paymentId,
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
            $this->paymentMethodId,
            $this->paymentId,
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
            $this->paymentMethodId,
            $this->paymentId,
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
                    $this->paymentMethodId,
                    $this->paymentId,
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
        ))->expectsException(LogicException::class)->expectsExceptionMessage('Cannot transition from pending to refunded.');
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

    public function testReserveGatewayCallThrowsWhenNotPending(): void
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
        ->expectsException(LogicException::class)->expectsExceptionMessage('Can only initiate gateway call for pending payment.');
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
}
