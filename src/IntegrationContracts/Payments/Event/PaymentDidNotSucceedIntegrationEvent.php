<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event;

use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ExternalEntityId;

class PaymentDidNotSucceedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public readonly ExternalEntityId $paymentId,
        public readonly ?ExternalEntityId $appliedTo,
    ) {
    }
}
