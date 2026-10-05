<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\EventHandler\Integration;

use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSource as DomainClaimSource;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSourceContext;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\DTO\ClaimSource as IntegrationClaimSource;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\DTO\SourceContext;

final class ClaimSourceMapper
{
    public function map(IntegrationClaimSource $source): DomainClaimSource
    {
        $context = match ($source->context) {
            SourceContext::Donation => ClaimSourceContext::Donation,
            SourceContext::Payment => ClaimSourceContext::Payment,
        };
        return DomainClaimSource::create($context, $source->id, $source->type);
    }

    /**
     * @param list<IntegrationClaimSource> $sources
     * @return list<DomainClaimSource>
     */
    public function mapMany(array $sources): array
    {
        return array_map($this->map(...), $sources);
    }
}
