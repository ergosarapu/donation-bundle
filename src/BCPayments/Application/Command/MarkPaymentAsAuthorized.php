<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Command;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;

class MarkPaymentAsAuthorized implements CommandInterface
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly Money $authorizedAmount,
        public readonly ?PaymentMethodResult $paymentMethodResult,
    ) {
    }
}
