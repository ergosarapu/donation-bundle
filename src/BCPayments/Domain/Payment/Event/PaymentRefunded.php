<?php

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event;

use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\ValueObject\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'payment.refunded')]
class PaymentRefunded extends AbstractTimestampedEvent
{
    public readonly PaymentStatus $status;

    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly Money $remainingAmount,
    ) {
        parent::__construct();
        $this->status = PaymentStatus::Refunded;
    }

}
