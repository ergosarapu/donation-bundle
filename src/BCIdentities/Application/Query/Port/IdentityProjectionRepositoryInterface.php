<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Port;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model\Identity;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\LegalIdentifier;

interface IdentityProjectionRepositoryInterface
{
    /**
     * @return list<Identity>
     */
    public function findByLegalIdentifier(LegalIdentifier $legalIdentifier): array;

    /**
     * @return list<Identity>
     */
    public function findByIban(string $iban): array;

    /**
     * @return list<Identity>
     */
    public function findByEmail(string $email): array;
}
