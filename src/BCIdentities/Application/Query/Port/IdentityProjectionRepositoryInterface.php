<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Port;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model\Identity;

interface IdentityProjectionRepositoryInterface
{
    /**
     * @return list<Identity>
     */
    public function findByNationalIdCode(string $nationalIdCode): array;

    /**
     * @return list<Identity>
     */
    public function findByIban(string $iban): array;

    /**
     * @return list<Identity>
     */
    public function findByEmail(string $email): array;
}
