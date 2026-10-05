<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model;

class ClaimPresentation
{
    use IdTrait;

    public const ATTRIBUTE_PERSON_NAME = 'person_name';
    public const ATTRIBUTE_RAW_NAME = 'raw_name';
    public const ATTRIBUTE_EMAIL = 'email';
    public const ATTRIBUTE_IBAN = 'iban';
    public const ATTRIBUTE_LEGAL_IDENTIFIER = 'legal_identifier';

    private Claim $claim;
    private string $attributeType;
    private ?string $value;
    private string $evidenceLevel;

    public function __construct(
        Claim $claim,
        string $attributeType,
        ?string $value,
        string $evidenceLevel,
    ) {
        $this->claim = $claim;
        $this->attributeType = $attributeType;
        $this->value = $value;
        $this->evidenceLevel = $evidenceLevel;
    }

    public function getClaim(): Claim
    {
        return $this->claim;
    }

    public function getAttributeType(): string
    {
        return $this->attributeType;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): void
    {
        $this->value = $value;
    }

    public function getEvidenceLevel(): string
    {
        return $this->evidenceLevel;
    }

    public function setEvidenceLevel(string $evidenceLevel): void
    {
        $this->evidenceLevel = $evidenceLevel;
    }
}
