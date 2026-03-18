<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\EntityClaim;

enum EntityClaimSource: string
{
    case Payments = 'Payments';
}
