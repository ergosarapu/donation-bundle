<?php

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Query;

use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\Query;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;

/**
 * @implements Query<Payment>
 */
class GetPayment implements Query
{
    public function __construct(
        public readonly PaymentId $paymentId,
    ) {}
}
