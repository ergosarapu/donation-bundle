<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Event;

use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimPresentation;
use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ClaimSource;

final class ClaimPresentedIntegrationEvent implements IntegrationEventInterface
{
    /**
     * @param list<ClaimPresentation> $presentations
     */
    public function __construct(
        public readonly ClaimSource $source,
        public readonly array $presentations,
    ) {
    }
}
