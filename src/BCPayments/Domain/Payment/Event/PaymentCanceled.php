<?php

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event;

use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\ValueObject\PaymentStatus;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'payment.canceled')]
class PaymentCanceled
{
    public readonly PaymentStatus $status;

    public function __construct(
        public readonly PaymentId $paymentId,
    ) {
        $this->status = PaymentStatus::Canceled;
    }

}
