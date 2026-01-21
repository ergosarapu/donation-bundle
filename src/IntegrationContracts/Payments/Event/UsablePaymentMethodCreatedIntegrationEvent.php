<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event;

use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodId;

class UsablePaymentMethodCreatedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public readonly PaymentMethodId $paymentMethodId,
    ) {
    }
}
