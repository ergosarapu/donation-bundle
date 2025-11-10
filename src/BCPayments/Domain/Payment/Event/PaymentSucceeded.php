<?php

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event;

use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'payment.succeeded')]
class PaymentSucceeded
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly Money $amount,
        public readonly ?PaymentAppliedToId $appliedTo = null,
    ) {
    }

}
