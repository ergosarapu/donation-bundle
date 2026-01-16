<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event;

use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodlId;

class PaymentMethodUsableIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public readonly PaymentMethodlId $paymentMethodId,
    ) {
    }
}
