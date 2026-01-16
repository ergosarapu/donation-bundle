<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodlId;
use InvalidArgumentException;
use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate(name: 'payment_method')]
class PaymentMethod extends BasicAggregateRoot
{
    #[Id]
    private PaymentMethodlId $id;
    private ?PaymentCredentialValue $value;

    public static function create(
        DateTimeImmutable $currentTime,
        PaymentMethodAction $paymentMethodAction,
        PaymentMethodResult $paymentMethodResult,
    ): self {
        $paymentMethod = new self();
        if (!$paymentMethodResult->isUsable()) {
            $paymentMethod->recordThat(new PaymentMethodUnusable(
                $currentTime,
                $paymentMethodAction,
                $paymentMethodResult->unusableReason(),
            ));
        } else {
            $paymentMethod->recordThat(new PaymentMethodUsable(
                $currentTime,
                $paymentMethodAction,
                $paymentMethodResult->value(),
            ));
        }
        return $paymentMethod;
    }

    public function update(
        DateTimeImmutable $currentTime,
        PaymentMethodAction $paymentMethodAction,
        PaymentMethodResult $result,
    ): void {
        if ($this->id->toString() !== $paymentMethodAction->paymentMethodId->toString()) {
            throw new InvalidArgumentException('Payment Method ID mismatch');
        }

        if ($result->isUsable()) {
            // Update to usable not supported
            return;
        }

        if ($this->value === null) {
            // Already unusable
            return;
        }

        $this->recordThat(new PaymentMethodUnusable(
            $currentTime,
            $paymentMethodAction,
            $result->unusableReason(),
        ));
    }

    #[Apply]
    protected function applyActivated(PaymentMethodUsable $event): void
    {
        $this->id = $event->paymentMethodAction->paymentMethodId;
        $this->value = $event->credentialValue;
    }

    #[Apply]
    protected function applyUnusable(PaymentMethodUnusable $event): void
    {
        $this->id = $event->paymentMethodAction->paymentMethodId;
        $this->value = null;
    }

    #[Apply]
    protected function applyUsePermitted(PaymentMethodUsePermitted $event): void
    {
    }

    #[Apply]
    protected function applyUseRejected(PaymentMethodUseRejected $event): void
    {
    }

    public function use(DateTimeImmutable $currentTime, PaymentMethodAction $paymentMethodAction): void
    {
        if ($this->id->toString() !== $paymentMethodAction->paymentMethodId->toString()) {
            throw new InvalidArgumentException('Payment Method ID mismatch');
        }

        if ($this->value === null) {
            $this->recordThat(new PaymentMethodUseRejected(
                $currentTime,
                $paymentMethodAction,
            ));
            return;
        }

        $this->recordThat(new PaymentMethodUsePermitted(
            $currentTime,
            $paymentMethodAction,
            $this->value,
        ));
    }
}
