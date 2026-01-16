<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Command;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodAction;

class UsePaymentMethod
{
    public function __construct(
        public readonly PaymentMethodAction $paymentMethodAction,
    ) {
    }
}
