<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Event;

use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimPresentation;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimerContext;
use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationEventInterface;
use ErgoSarapu\DonationBundle\IntegrationContracts\ValueObject\EntityId;

final class ClaimPresentedIntegrationEvent implements IntegrationEventInterface
{
    /**
     * @param list<ClaimPresentation> $presentations
     */
    public function __construct(
        public readonly EntityId $claimerId,
        public readonly ClaimerContext $claimerContext,
        public readonly array $presentations,
    ) {
    }
}
