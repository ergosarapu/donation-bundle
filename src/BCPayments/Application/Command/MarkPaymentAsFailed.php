<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Command;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;

class MarkPaymentAsFailed implements CommandInterface
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly ?PaymentMethodResult $paymentMethodResult,
    ) {
    }
}
