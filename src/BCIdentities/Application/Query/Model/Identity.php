<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model;

class Identity
{
    private string $identityId;
    /** @var list<Claim> */
    private iterable $claims = [];

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
        $emails = [];
        foreach ($this->claims as $claim) {
            foreach ($claim->getPresentations() as $presentation) {
                $email = $presentation->getEmail();
                if ($email !== null) {
                    $emails[$email] = $email;
                }
            }
        }

        return array_values($emails);
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
