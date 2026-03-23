<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Infrastructure\Adapter;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\IdentityLookupInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Port\IdentityProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\IdentityId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;

final class ProjectorIdentityLookup implements IdentityLookupInterface
{
    public function __construct(
        private readonly IdentityProjectionRepositoryInterface $identityProjectionRepository,
    ) {
    }

    /**
     * @return list<IdentityId>
     */
    public function lookup(
        ?Email $email = null,
        ?Iban $iban = null,
        ?NationalIdCode $nationalIdCode = null,
    ): array {
        /** @var array<string, IdentityId> $matches */
        $matches = [];

        if ($nationalIdCode !== null) {
            $this->addMatches($matches, $this->identityProjectionRepository->findByNationalIdCode($nationalIdCode->value));
        }

        if ($iban !== null) {
            $this->addMatches($matches, $this->identityProjectionRepository->findByIban($iban->value));
        }

        if ($email !== null) {
            $this->addMatches($matches, $this->identityProjectionRepository->findByEmail($email->toString()));
        }

        return array_values($matches);
    }

    /**
     * @param array<string, IdentityId> $matches
     * @param list<\ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model\Identity> $identities
     */
    private function addMatches(array &$matches, array $identities): void
    {
        foreach ($identities as $identity) {
            $matches[$identity->getIdentityId()] = IdentityId::fromString($identity->getIdentityId());
        }
    }
}
