<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Event;

use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\DTO\ClaimSource;
use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationEventInterface;

final class ClaimCorrelatedSourcesUpdatedIntegrationEvent implements IntegrationEventInterface
{
    /**
     * @param list<ClaimSource> $correlatedSources
     */
    public function __construct(
        public readonly ClaimSource $claimSource,
        public readonly array $correlatedSources,
    ) {
    }
}
