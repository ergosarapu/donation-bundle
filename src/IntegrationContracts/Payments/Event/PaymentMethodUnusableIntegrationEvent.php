<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event;

use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationEventInterface;
use ErgoSarapu\DonationBundle\IntegrationContracts\ValueObject\EntityId;

class PaymentMethodUnusableIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public readonly EntityId $paymentMethodId,
        public readonly EntityId $createdFor,
    ) {
    }
}
