<?php

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event;

use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'payment.did_not_succeed')]
class PaymentDidNotSucceed
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly ?PaymentAppliedToId $appliedTo = null,
    ) {
    }

}
