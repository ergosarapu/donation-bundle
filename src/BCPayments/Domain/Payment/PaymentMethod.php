<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ExternalEntityId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodId;
use InvalidArgumentException;
use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate(name: 'payment_method')]
class PaymentMethod extends BasicAggregateRoot
{
    #[Id]
    private PaymentMethodId $id;
    private ?PaymentCredentialValue $value;
    private ExternalEntityId $createFor;

    public static function create(
        DateTimeImmutable $currentTime,
        PaymentMethodId $paymentMethodId,
        PaymentMethodResult $paymentMethodResult,
        ExternalEntityId $createFor,
    ): self {
        $paymentMethod = new self();
        if (!$paymentMethodResult->isUsable()) {
            $paymentMethod->recordThat(new UnusablePaymentMethodCreated(
                $currentTime,
                $paymentMethodId,
                $paymentMethodResult->unusableReason(),
                $createFor,
            ));
        } else {
            $paymentMethod->recordThat(new UsablePaymentMethodCreated(
                $currentTime,
                $paymentMethodId,
                $paymentMethodResult->value(),
                $createFor,
            ));
        }
        return $paymentMethod;
    }

    public function update(
        DateTimeImmutable $currentTime,
        PaymentMethodId $paymentMethodId,
        PaymentMethodResult $result,
    ): void {
        if ($this->id->toString() !== $paymentMethodId->toString()) {
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
            $paymentMethodId,
            $result->unusableReason(),
            $this->createFor,
        ));
    }

    #[Apply]
    protected function applyUsableCreated(UsablePaymentMethodCreated $event): void
    {
        $this->id = $event->paymentMethodId;
        $this->value = $event->credentialValue;
        $this->createFor = $event->createdFor;
    }

    #[Apply]
    protected function applyUnusableCreated(UnusablePaymentMethodCreated $event): void
    {
        $this->id = $event->paymentMethodId;
        $this->value = null;
        $this->createFor = $event->createdFor;
    }

    #[Apply]
    protected function applyUnusable(PaymentMethodUnusable $event): void
    {
        $this->id = $event->paymentMethodId;
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
