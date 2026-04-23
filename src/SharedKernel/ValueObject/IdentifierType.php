<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

enum IdentifierType: string
{
    case NationalIdNumber = 'national_id_number';
    case OrganisationRegNumber = 'organisation_reg_number';
}
