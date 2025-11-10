<?php

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event;

use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;

class PaymentDidNotSucceedIntegrationEvent
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly ?PaymentAppliedToId $appliedTo,
    ) {
    }
}
