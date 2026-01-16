<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Command;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodAction;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;

class UpdatePaymentMethod
{
    public function __construct(
        public readonly PaymentMethodAction $action,
        public readonly PaymentMethodResult $result,
    ) {
    }
}
