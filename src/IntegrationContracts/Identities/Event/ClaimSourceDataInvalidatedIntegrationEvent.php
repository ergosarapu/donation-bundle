<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Event;

use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\DTO\ClaimSource;
use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationEventInterface;

/**
 * Event to be emitted when claim source data has been invalidated.
 * The correlated sources remain unchanged.
 */
final class ClaimSourceDataInvalidatedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public readonly ClaimSource $claimSource
    ) {
    }
}
