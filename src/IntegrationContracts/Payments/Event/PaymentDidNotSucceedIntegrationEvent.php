<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event;

use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationEventInterface;
use ErgoSarapu\DonationBundle\IntegrationContracts\ValueObject\EntityId;

class PaymentDidNotSucceedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public readonly EntityId $paymentId,
        public readonly ?EntityId $appliedTo,
    ) {
    }
}
