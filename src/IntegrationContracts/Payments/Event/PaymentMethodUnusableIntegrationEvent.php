<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event;

use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ExternalEntityId;

class PaymentMethodUnusableIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public readonly ExternalEntityId $paymentMethodId,
        public readonly ExternalEntityId $createdFor,
    ) {
    }
}
