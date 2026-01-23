<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event;

use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;

class PaymentSucceededIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly Money $amount,
        public readonly ?PaymentAppliedToId $appliedTo,
    ) {
    }
}
