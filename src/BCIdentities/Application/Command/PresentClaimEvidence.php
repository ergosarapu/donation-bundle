<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Command;

use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimEvidenceLevel;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSource;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\LegalIdentifier;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\RawName;

final class PresentClaimEvidence implements CommandInterface
{
    /**
     * @param array{0: PersonName|RawName|Email|Iban|LegalIdentifier|class-string, 1: ClaimEvidenceLevel}[] $presentations
     * @param list<ClaimSource> $correlatedSources
     */
    public function __construct(
        public readonly ClaimSource $claimSource,
        public readonly array $presentations,
        public readonly array $correlatedSources,
    ) {
    }
}
