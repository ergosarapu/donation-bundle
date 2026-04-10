<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim;

enum ClaimEvidenceLevel: string
{
    case Observed = 'observed';
    case VerifiedByUser = 'verified_by_user';
    case Verified = 'verified';

    public function rank(): int
    {
        if ($this === self::Observed) {
            return 1;
        }
        if ($this === self::VerifiedByUser) {
            return 2;
        }
        // self::Verified
        return 3;
    }
}
