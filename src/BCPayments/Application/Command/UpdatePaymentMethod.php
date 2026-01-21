<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Command;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodAction;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;

class UpdatePaymentMethod implements CommandInterface
{
    public function __construct(
        public readonly PaymentMethodAction $action,
        public readonly PaymentMethodResult $result,
    ) {
    }
}
