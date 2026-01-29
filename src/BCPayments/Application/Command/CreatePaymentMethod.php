<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Command;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodId;

class CreatePaymentMethod implements CommandInterface
{
    public function __construct(
        public readonly PaymentMethodId $paymentMethodId,
        public readonly PaymentMethodResult $paymentMethodResult,
    ) {
    }
}
