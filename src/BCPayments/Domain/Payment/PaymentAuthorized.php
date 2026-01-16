<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'payment.authorized')]
class PaymentAuthorized extends AbstractTimestampedEvent
{
    public readonly PaymentStatus $status;

    public function __construct(
        DateTimeImmutable $occurredOn,
        public readonly PaymentId $paymentId,
        public readonly Money $authorizedAmount,
        public readonly ?PaymentAppliedToId $appliedTo = null,
        public readonly ?PaymentMethodAction $paymentMethodAction = null,
        public readonly ?PaymentMethodResult $paymentMethodResult = null,
    ) {
        parent::__construct($occurredOn);
        $this->status = PaymentStatus::Authorized;
    }

}
