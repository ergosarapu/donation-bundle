<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\Query;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodlId;

class GetRecurringPlanByPaymentMethod implements Query
{
    public function __construct(public readonly PaymentMethodlId $paymentMethodId)
    {
    }
}
