<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model;

class Identity
{
    private string $identityId;
    private ?string $givenName = null;
    private ?string $familyName = null;
    private ?string $legalIdentifier = null;
    /** @var iterable<int, IdentityRawName> */
    private iterable $rawNames = [];
    /** @var iterable<int, IdentityEmail> */
    private iterable $emails = [];
    /** @var iterable<int, IdentityIban> */
    private iterable $ibans = [];

    public function getIdentityId(): string
    {
        return $this->identityId;
    }

    public function setIdentityId(string $identityId): void
    {
        $this->identityId = $identityId;
    }

    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    public function setGivenName(?string $givenName): void
    {
        $this->givenName = $givenName;
    }

    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    public function setFamilyName(?string $familyName): void
    {
        $this->familyName = $familyName;
    }

    /**
     * @return list<string>
     */
    public function getRawNames(): array
    {
        $result = [];
        foreach ($this->rawNames as $rawName) {
            $result[] = $rawName->getRawName();
        }
        return $result;
    }

    public function addRawName(string $rawName): void
    {
        $this->appendToCollection($this->rawNames, new IdentityRawName($this, $rawName));
    }

    /**
     * @return list<string>
     */
    public function getEmails(): array
    {
        $result = [];
        foreach ($this->emails as $email) {
            $result[] = $email->getEmail();
        }
        return $result;
    }

    public function addEmail(string $email): void
    {
        $this->appendToCollection($this->emails, new IdentityEmail($this, $email));
    }

    /**
     * @return list<string>
     */
    public function getIbans(): array
    {
        $result = [];
        foreach ($this->ibans as $iban) {
            $result[] = $iban->getIban();
        }
        return $result;
    }

    public function addIban(string $iban): void
    {
        $this->appendToCollection($this->ibans, new IdentityIban($this, $iban));
    }

    public function getLegalIdentifier(): ?string
    {
        return $this->legalIdentifier;
    }

    public function setLegalIdentifier(?string $legalIdentifier): void
    {
        $this->legalIdentifier = $legalIdentifier;
    }

    /**
     * @template T of object
     * @param iterable<int, T> $items
     * @param T $item
     */
    private function appendToCollection(iterable &$items, object $item): void
    {
        if (is_array($items)) {
            $items[] = $item;

            return;
        }

        if (method_exists($items, 'add')) {
            $items->add($item);

            return;
        }
    }

}
