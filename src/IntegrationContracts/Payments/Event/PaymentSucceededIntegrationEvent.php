<?php

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event;

use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;

class PaymentSucceededIntegrationEvent
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly Money $amount,
        public readonly ?PaymentAppliedToId $appliedTo,
    ) {
    }
}
