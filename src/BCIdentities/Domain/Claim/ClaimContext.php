<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim;

enum ClaimContext: string
{
    case Donation = 'donation';
    case Payment = 'payment';
}
