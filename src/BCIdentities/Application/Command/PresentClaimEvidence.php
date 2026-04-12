<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Command;

use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSource;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimPresentation;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;

final class PresentClaimEvidence implements CommandInterface
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
