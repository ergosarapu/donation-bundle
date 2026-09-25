<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Identities\DTO;

enum SourceContext: string
{
    case Donation = 'donation';
    case Payment = 'payment';
}
