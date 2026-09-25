<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model;

class ClaimPresentation
{
    use IdTrait;

    private Claim $claim;
    private ?string $evidenceLevel;
    private ?string $givenName = null;
    private ?string $familyName = null;
    private ?string $legalIdentifier = null;
    private ?string $rawName = null;
    private ?string $email = null;
    private ?string $iban = null;

    public function __construct(Claim $claim, ?string $evidenceLevel)
    {
        $this->claim = $claim;
        $this->evidenceLevel = $evidenceLevel;
    }

    public function getClaim(): Claim
    {
        return $this->claim;
    }

    public function getEvidenceLevel(): ?string
    {
        return $this->evidenceLevel;
    }

    public function setEvidenceLevel(?string $evidenceLevel): void
    {
        $this->evidenceLevel = $evidenceLevel;
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

    public function getLegalIdentifier(): ?string
    {
        return $this->legalIdentifier;
    }

    public function setLegalIdentifier(?string $legalIdentifier): void
    {
        $this->legalIdentifier = $legalIdentifier;
    }

    public function getRawName(): ?string
    {
        return $this->rawName;
    }

    public function setRawName(?string $rawName): void
    {
        $this->rawName = $rawName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(?string $iban): void
    {
        $this->iban = $iban;
    }
}
