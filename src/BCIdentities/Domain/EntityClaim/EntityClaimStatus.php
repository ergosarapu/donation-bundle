<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\EntityClaim;

enum EntityClaimStatus: string
{
    case Pending = 'pending';
    case Linked = 'linked';
}
