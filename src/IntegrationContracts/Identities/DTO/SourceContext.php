<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Identities\DTO;

enum SourceContext: string
{
    case Donations = 'donations';
    case Payments = 'payments';
}
