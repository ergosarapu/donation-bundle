<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Port;

use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\OrganisationRegCode;

interface IdentityLookupInterface
{
    /**
     * @return list<IdentityId>
     */
    public function lookup(
        ?Email $email = null,
        ?Iban $iban = null,
        ?NationalIdCode $nationalIdCode = null,
        ?OrganisationRegCode $organisationRegCode = null,
    ): array;
}
