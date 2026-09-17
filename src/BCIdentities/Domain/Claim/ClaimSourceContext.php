<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim;

enum ClaimSourceContext: string
{
    case Donations = 'donations';
    case Payments = 'payments';
}
