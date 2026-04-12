<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Query;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\Query;

class GetPaymentMethod implements Query
{
    public function __construct(
        public readonly PaymentMethodId $paymentMethodId,
    ) {
    }
}
