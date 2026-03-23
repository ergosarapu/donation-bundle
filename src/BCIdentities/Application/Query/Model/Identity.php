<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model;

final class Identity
{
    private string $identityId;
    private ?string $givenName = null;
    private ?string $familyName = null;
    private ?string $nationalIdCode = null;
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
        return array_values(array_map(
            static fn (IdentityRawName $rawName): string => $rawName->getRawName(),
            $this->rawNames->toArray(),
        ));
    }

    public function addRawName(string $rawName): void
    {
        foreach ($this->rawNames as $currentRawName) {
            if ($currentRawName->getRawName() === $rawName) {
                return;
            }
        }

        $this->appendToCollection($this->rawNames, new IdentityRawName($this, $rawName));
    }

    /**
     * @return list<string>
     */
    public function getEmails(): array
    {
        return array_values(array_map(
            static fn (IdentityEmail $email): string => $email->getEmail(),
            $this->emails->toArray(),
        ));
    }

    public function addEmail(string $email): void
    {
        foreach ($this->emails as $currentEmail) {
            if ($currentEmail->getEmail() === $email) {
                return;
            }
        }

        $this->appendToCollection($this->emails, new IdentityEmail($this, $email));
    }

    /**
     * @return list<string>
     */
    public function getIbans(): array
    {
        return array_values(array_map(
            static fn (IdentityIban $iban): string => $iban->getIban(),
            $this->ibans->toArray(),
        ));
    }

    public function addIban(string $iban): void
    {
        foreach ($this->ibans as $currentIban) {
            if ($currentIban->getIban() === $iban) {
                return;
            }
        }

        $this->appendToCollection($this->ibans, new IdentityIban($this, $iban));
    }

    public function getNationalIdCode(): ?string
    {
        return $this->nationalIdCode;
    }

    public function setNationalIdCode(?string $nationalIdCode): void
    {
        $this->nationalIdCode = $nationalIdCode;
    }

    private function appendToCollection(iterable &$items, object $item): void
    {
        if (is_array($items)) {
            $items[] = $item;

            return;
        }

        if (method_exists($items, 'add')) {
            $items->add($item);
        }
    }

}
