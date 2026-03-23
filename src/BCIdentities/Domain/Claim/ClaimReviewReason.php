<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim;

enum ClaimReviewReason: string
{
    case MultipleIdentityMatches = 'multiple_identity_matches';
    case AttributeBelowThreshold = 'attribute_below_threshold';
    case ConflictingClaimValue = 'conflicting_claim_value';
}
