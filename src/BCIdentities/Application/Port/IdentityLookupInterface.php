<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Port;

use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\LegalIdentifier;

interface IdentityLookupInterface
{
    /**
     * @return list<IdentityId>
     */
    public function lookup(
        ?Email $email = null,
        ?Iban $iban = null,
        ?LegalIdentifier $legalIdentifier = null,
    ): array;
}
