<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Command;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodAction;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;

class UsePaymentMethod implements CommandInterface
{
    public function __construct(
        public readonly PaymentMethodAction $paymentMethodAction,
    ) {
    }
}
