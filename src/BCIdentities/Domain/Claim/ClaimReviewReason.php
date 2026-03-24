<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim;

enum ClaimReviewReason: string
{
    case MultipleIdentityMatches = 'multiple_identity_matches';
    case MergeConflict = 'merge_conflict';
}
