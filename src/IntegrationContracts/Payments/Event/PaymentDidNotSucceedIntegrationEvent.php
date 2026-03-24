<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event;

use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ExternalEntityId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;

class PaymentDidNotSucceedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly ?ExternalEntityId $appliedTo,
    ) {
    }
}
