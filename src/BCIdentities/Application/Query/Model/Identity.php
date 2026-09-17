<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model;

class Identity
{
    private string $identityId;

    public function getIdentityId(): string
    {
        return $this->identityId;
    }

    public function setIdentityId(string $identityId): void
    {
        $this->identityId = $identityId;
    }

    /**
     * @return list<array{givenName: string, familyName: string}>
     */
    public function getPersonNames(): array
    {
        // TODO: Load person names from connected component claims
        return [];
    }

    /**
     * @return list<string>
     */
    public function getPersonNamesSummary(): array
    {
        $result = [];
        foreach ($this->getPersonNames() as $personName) {
            $result[] = trim(sprintf('%s %s', $personName['givenName'], $personName['familyName']));
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    public function getRawNames(): array
    {
        // TODO: Load person names from connected component claims
        return [];
    }

    /**
     * @return list<string>
     */
    public function getEmails(): array
    {
        // TODO: Load person names from connected component claims
        return [];
    }

    /**
     * @return list<string>
     */
    public function getIbans(): array
    {
        // TODO: Load person names from connected component claims
        return [];
    }

    /**
     * @return list<string>
     */
    public function getLegalIdentifiers(): array
    {
        // TODO: Load person names from connected component claims
        return [];
    }
}
