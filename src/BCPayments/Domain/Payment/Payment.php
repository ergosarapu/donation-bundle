<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\URL;
use LogicException;
use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

/** @package ErgoSarapu\DonationBundle\BCPayments\Domain\Payment */
#[Aggregate(name: 'payment')]
class Payment extends BasicAggregateRoot
{
    #[Id]
    private PaymentId $id;
    private Money $amount;
    private Gateway $gateway;
    private ShortDescription $description;
    private ?Email $email;
    private PaymentStatus $status;
    private ?PaymentAppliedToId $appliedTo = null;
    private bool $succeedRecorded = false;
    private bool $gatewayCallReserved = false;
    private ?PaymentMethodAction $paymentMethodAction;

    public static function initiate(
        DateTimeImmutable $currentTime,
        PaymentRequest $paymentRequest,
        ?PaymentMethodAction $paymentMethodAction,
    ): self {
        $payment = new self();
        if ($paymentMethodAction !== null) {
            self::validatePaymentIds($paymentMethodAction, $paymentRequest);
        }

        $payment->recordThat(new PaymentInitiated(
            $currentTime,
            $paymentRequest->paymentId,
            $paymentRequest->amount,
            $paymentRequest->gateway,
            $paymentRequest->description,
            $paymentRequest->appliedTo,
            $paymentRequest->email,
            $paymentMethodAction,
        ));
        return $payment;
    }

    private static function validatePaymentIds(PaymentMethodAction $paymentMethodAction, PaymentRequest $paymentRequest): void
    {
        if ($paymentMethodAction->paymentId !== $paymentRequest->paymentId) {
            throw new LogicException('Payment method action paymentId does not match payment request paymentId.');
        }
    }

    #[Apply(PaymentInitiated::class)]
    protected function applyInitiated(PaymentInitiated $event): void
    {
        $this->id = $event->paymentId;
        $this->amount = $event->amount;
        $this->status = $event->status;
        $this->appliedTo = $event->appliedTo;
        $this->gateway = $event->gateway;
        $this->description = $event->description;
        $this->email = $event->email;
        $this->paymentMethodAction = $event->paymentMethodAction;
    }

    // #[Apply]
    // protected function applyPaymentPending(PaymentPending $event): void
    // {
    //     $this->status = $event->status;
    // }

    #[Apply]
    protected function applyPaymentAuthorized(PaymentAuthorized $event): void
    {
        $this->status = $event->status;
        $this->amount = $event->authorizedAmount;
    }

    #[Apply]
    protected function applyPaymentCaptured(PaymentCaptured $event): void
    {
        $this->status = $event->status;
        $this->amount = $event->capturedAmount;
    }

    #[Apply]
    protected function applyPaymentCanceled(PaymentCanceled $event): void
    {
        $this->status = $event->status;
    }

    #[Apply]
    protected function applyPaymentFailed(PaymentFailed $event): void
    {
        $this->status = $event->status;
    }

    #[Apply]
    protected function applyPaymentRefunded(PaymentRefunded $event): void
    {
        $this->status = $event->status;
        $this->amount = $event->remainingAmount;
    }

    #[Apply]
    protected function applyPaymentSucceeded(PaymentSucceeded $event): void
    {
        $this->succeedRecorded = true;
    }

    #[Apply]
    protected function applyPaymentDidNotSucceeded(PaymentDidNotSucceed $event): void
    {
    }


    #[Apply]
    protected function applyReservedForGatewayCall(PaymentReservedForGatewayCall $event): void
    {
        $this->gatewayCallReserved = true;
    }

    #[Apply]
    protected function applyReleasedForGatewayCall(PaymentReleasedForGatewayCall $event): void
    {
        $this->gatewayCallReserved = false;
    }

    #[Apply]
    protected function applyRedirectURLCaptureInitiated(PaymentRedirectUrlSetUp $event): void
    {
    }

    public function markAuthorized(DateTimeImmutable $currentTime, Money $authorizedAmount, ?PaymentMethodResult $paymentMethodResult): void
    {
        // Idempotency guard
        if ($this->status === PaymentStatus::Authorized) {
            return;
        }
        $this->validateTransitionToAuthorized();
        $this->recordThat(new PaymentAuthorized($currentTime, $this->id, $authorizedAmount, $this->appliedTo, $this->paymentMethodAction, $paymentMethodResult));
        $this->recordSucceeded($currentTime);
    }

    private function recordSucceeded(DateTimeImmutable $currentTime): void
    {
        if ($this->succeedRecorded) {
            // "Succeed" can mean authorized, captured or settled - therefore we want to record it only once
            return;
        }
        $this->recordThat(new PaymentSucceeded($currentTime, $this->id, $this->amount, $this->appliedTo));
    }

    public function validateTransitionToAuthorized(): void
    {
        if ($this->status === PaymentStatus::Pending) {
            return;
        }
        $this->failTransitionValidation($this->status, PaymentStatus::Authorized);
    }

    public function markCaptured(DateTimeImmutable $currentTime, Money $capturedAmount, ?PaymentMethodResult $paymentMethodResult): void
    {
        // Idempotency guard
        if ($this->status === PaymentStatus::Captured) {
            return;
        }
        $this->validateTransitionToCaptured();
        $this->recordThat(new PaymentCaptured($currentTime, $this->id, $capturedAmount, $this->appliedTo, $this->paymentMethodAction, $paymentMethodResult));
        $this->recordSucceeded($currentTime);
    }

    public function validateTransitionToCaptured(): void
    {
        if ($this->status === PaymentStatus::Pending) {
            return;
        }
        if ($this->status === PaymentStatus::Authorized) {
            return;
        }
        $this->failTransitionValidation($this->status, PaymentStatus::Captured);
    }

    public function markCanceled(DateTimeImmutable $currentTime): void
    {
        // Idempotency guard
        if ($this->status === PaymentStatus::Canceled) {
            return;
        }
        $this->validateTransitionToCanceled();
        $this->recordThat(new PaymentCanceled($currentTime, $this->id));
        $this->recordThat(new PaymentDidNotSucceed($currentTime, $this->id, $this->appliedTo));
    }

    public function validateTransitionToCanceled(): void
    {
        if ($this->status === PaymentStatus::Pending) {
            return;
        }
        $this->failTransitionValidation($this->status, PaymentStatus::Canceled);
    }

    public function markFailed(DateTimeImmutable $currentTime, ?PaymentMethodResult $paymentMethodResult): void
    {
        // Idempotency guard
        if ($this->status === PaymentStatus::Failed) {
            return;
        }
        $this->validateTransitionToFailed();
        $this->recordThat(new PaymentFailed($currentTime, $this->id, $this->appliedTo, $this->paymentMethodAction, $paymentMethodResult));
        $this->recordThat(new PaymentDidNotSucceed($currentTime, $this->id, $this->appliedTo));
    }

    public function validateTransitionToFailed(): void
    {
        if ($this->status === PaymentStatus::Pending) {
            return;
        }
        if ($this->status === PaymentStatus::Authorized) {
            return;
        }
        $this->failTransitionValidation($this->status, PaymentStatus::Failed);
    }

    public function markRefunded(DateTimeImmutable $currentTime, Money $remainingAmount): void
    {
        // Idempotency guard
        if ($this->status === PaymentStatus::Refunded) {
            return;
        }
        $this->validateTransitionToRefunded();
        $this->recordThat(new PaymentRefunded($currentTime, $this->id, $remainingAmount));
    }

    public function validateTransitionToRefunded(): void
    {
        if ($this->status === PaymentStatus::Captured) {
            return;
        }
        $this->failTransitionValidation($this->status, PaymentStatus::Refunded);
    }

    private function failTransitionValidation(PaymentStatus $from, PaymentStatus $to): void
    {
        throw new LogicException('Cannot transition from ' . $from->value . ' to ' . $to->value . '.');
    }

    public function reserveGatewayCall(DateTimeImmutable $currentTime): ?GatewayPaymentRequest
    {
        if ($this->gatewayCallReserved) {
            return null;
        }

        if ($this->status !== PaymentStatus::Pending) {
            throw new LogicException('Can only initiate gateway call for pending payment.');
        }

        $this->recordThat(new PaymentReservedForGatewayCall($currentTime, $this->id));
        return new GatewayPaymentRequest(
            $this->id,
            $this->amount,
            $this->gateway,
            $this->description,
            $this->email,
        );
    }

    public function releaseGatewayCall(DateTimeImmutable $currentTime): void
    {
        if (!$this->gatewayCallReserved) {
            return;
        }
        $this->recordThat(new PaymentReleasedForGatewayCall($currentTime, $this->id));
    }

    public function setRedirectURL(DateTimeImmutable $currentTime, URL $redirectUrl): void
    {
        $this->recordThat(new PaymentRedirectUrlSetUp($currentTime, $this->id, $redirectUrl));
    }
}
