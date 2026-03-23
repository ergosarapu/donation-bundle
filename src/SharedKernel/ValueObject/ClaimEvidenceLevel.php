<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

enum ClaimEvidenceLevel: string
{
    case Observed = 'observed';
    case VerifiedByUser = 'verified_by_user';
    case Verified = 'verified';

    public function rank(): int
    {
        return match ($this) {
            self::Observed => 1,
            self::VerifiedByUser => 2,
            self::Verified => 3,
        };
    }
}
