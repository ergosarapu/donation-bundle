<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

enum ClaimContext: string
{
    case Donation = 'donation';
    case Payment = 'payment';
}
