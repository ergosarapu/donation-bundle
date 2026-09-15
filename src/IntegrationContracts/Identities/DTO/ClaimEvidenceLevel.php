<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Identities\DTO;

enum ClaimEvidenceLevel: string
{
    case Observed = 'observed';
    case VerifiedByUser = 'verified_by_user';
    case Verified = 'verified';
}
