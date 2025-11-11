<?php

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event;

use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\ValueObject\PaymentStatus;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'payment.pending')]
class PaymentPending extends AbstractTimestampedEvent
{
    public readonly PaymentStatus $status;

    public function __construct(
        public readonly PaymentId $paymentId,
    ) {
        parent::__construct();
        $this->status = PaymentStatus::Pending;
    }

}
