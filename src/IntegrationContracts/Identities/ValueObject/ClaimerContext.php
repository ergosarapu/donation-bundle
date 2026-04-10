<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject;

enum ClaimerContext: string
{
    case Donation = 'donation';
    case Payment = 'payment';
}
