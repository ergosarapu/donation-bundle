<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event\PaymentAuthorized;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event\PaymentCanceled;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event\PaymentCaptured;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event\PaymentDidNotSucceed;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event\PaymentFailed;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event\PaymentInitiated;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event\PaymentPending;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event\PaymentRefunded;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event\PaymentSucceeded;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\ValueObject\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\URL;
use LogicException;
use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate(name: 'payment')]
class Payment extends BasicAggregateRoot
{
    #[Id]
    private PaymentId $id;
    private Money $amount;
    private PaymentStatus $status;
    private ?PaymentAppliedToId $appliedTo = null;
    private bool $succeedRecorded = false;

    public static function initiate(
        DateTimeImmutable $currentTime,
        PaymentId $id,
        Money $amount,
        Gateway $gateway,
        ShortDescription $description,
        URL $redirectUrl,
        ?PaymentAppliedToId $appliedTo = null,
    ): self {
        $payment = new self();
        $payment->recordThat(new PaymentInitiated(
            $currentTime,
            $id,
            $amount,
            PaymentStatus::Pending,
            $gateway,
            $description,
            $redirectUrl,
            $appliedTo,
        ));
        return $payment;
    }

    #[Apply(PaymentInitiated::class)]
    protected function applyInitiated(PaymentInitiated $event): void
    {
        $this->id = $event->paymentId;
        $this->amount = $event->amount;
        $this->status = $event->status;
        $this->appliedTo = $event->appliedTo;
        // $this->gateway = $event->gateway;
        // $this->description = $event->description;
        // $this->captureUrl = $event->captureUrl;
    }

    #[Apply]
    protected function applyPaymentPending(PaymentPending $event): void
    {
        $this->status = $event->status;
    }

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

    public function markPending(DateTimeImmutable $currentTime): void
    {
        // Idempotency guard
        if ($this->status === PaymentStatus::Pending) {
            return;
        }
        $this->canTransitionToPending(true);
        $this->recordThat(new PaymentPending($currentTime, $this->id));
    }

    public function canTransitionToPending(bool $throw = false): bool
    {
        return $this->canTransition($this->status, PaymentStatus::Pending, [], $throw);
    }

    public function markAuthorized(DateTimeImmutable $currentTime, Money $authorizedAmount): void
    {
        // Idempotency guard
        if ($this->status === PaymentStatus::Authorized) {
            if (!$this->amount->equals($authorizedAmount)) {
                throw new LogicException('Payment already Authorized with different amount, existing: ' . $this->amount . ', new: ' . $authorizedAmount);
            }
            return;
        }
        $this->canTransitionToAuthorized(true);
        $this->recordThat(new PaymentAuthorized($currentTime, $this->id, $authorizedAmount));
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

    public function canTransitionToAuthorized(bool $throw = false): bool
    {
        return $this->canTransition($this->status, PaymentStatus::Authorized, [PaymentStatus::Pending], $throw);
    }

    public function markCaptured(DateTimeImmutable $currentTime, Money $capturedAmount): void
    {
        // Idempotency guard
        if ($this->status === PaymentStatus::Captured) {
            if (!$this->amount->equals($capturedAmount)) {
                throw new LogicException('Payment already Captured with different amount, existing: ' . $this->amount . ', new: ' . $capturedAmount);
            }
            return;
        }
        $this->canTransitionToCaptured(true);
        $this->recordThat(new PaymentCaptured($currentTime, $this->id, $capturedAmount, $this->appliedTo));
        $this->recordSucceeded($currentTime);
    }

    public function canTransitionToCaptured(bool $throw = false): bool
    {
        return $this->canTransition($this->status, PaymentStatus::Captured, [PaymentStatus::Pending, PaymentStatus::Authorized], $throw);
    }

    public function markCanceled(DateTimeImmutable $currentTime): void
    {
        // Idempotency guard
        if ($this->status === PaymentStatus::Canceled) {
            return;
        }
        $this->canTransitionToCanceled(true);
        $this->recordThat(new PaymentCanceled($currentTime, $this->id));
        $this->recordThat(new PaymentDidNotSucceed($currentTime, $this->id, $this->appliedTo));
    }

    public function canTransitionToCanceled(bool $throw = false): bool
    {
        return $this->canTransition($this->status, PaymentStatus::Canceled, [PaymentStatus::Pending, PaymentStatus::Authorized], $throw);
    }

    public function markFailed(DateTimeImmutable $currentTime): void
    {
        // Idempotency guard
        if ($this->status === PaymentStatus::Failed) {
            return;
        }
        $this->canTransitionToFailed(true);
        $this->recordThat(new PaymentFailed($currentTime, $this->id, $this->appliedTo));
        $this->recordThat(new PaymentDidNotSucceed($currentTime, $this->id, $this->appliedTo));
    }

    public function canTransitionToFailed(bool $throw = false): bool
    {
        return $this->canTransition($this->status, PaymentStatus::Failed, [PaymentStatus::Pending, PaymentStatus::Authorized], $throw);
    }

    public function markRefunded(DateTimeImmutable $currentTime, Money $remainingAmount): void
    {
        // Idempotency guard
        if ($this->status === PaymentStatus::Refunded) {
            if (!$this->amount->equals($remainingAmount)) {
                throw new LogicException('Payment already Refunded with different amount, existing: ' . $this->amount . ', new: ' . $remainingAmount);
            }
            return;
        }
        $this->canTransitionToRefunded(true);
        $this->recordThat(new PaymentRefunded($currentTime, $this->id, $remainingAmount));
    }

    public function canTransitionToRefunded(bool $throw = false): bool
    {
        return $this->canTransition($this->status, PaymentStatus::Refunded, [PaymentStatus::Captured], $throw);
    }

    /**
     * @param array<PaymentStatus> $allowedFrom
     */
    private function canTransition(PaymentStatus $from, PaymentStatus $to, array $allowedFrom, bool $throw = false): bool
    {
        $canTransition = in_array($from, $allowedFrom);
        if ($throw && !$canTransition) {
            throw new LogicException('Cannot transition from ' . $from->value . ' to ' . $to->value . '.');
        }
        return $canTransition;
    }
}
