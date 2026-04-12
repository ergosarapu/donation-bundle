<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\OrganisationRegCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\RawName;

final class ClaimPresentation
{
    /**
     * @param PersonName|RawName|Email|Iban|NationalIdCode|OrganisationRegCode|class-string<PersonName>|class-string<RawName>|class-string<Email>|class-string<Iban>|class-string<NationalIdCode>|class-string<OrganisationRegCode> $value
     */
    public function __construct(
        public readonly mixed $value,
        public readonly ClaimEvidenceLevel $evidenceLevel,
    ) {
    }

    /**
     * @param PersonName|RawName|Email|Iban|NationalIdCode|OrganisationRegCode $value
     */
    public static function forValue(object $value, ClaimEvidenceLevel $evidenceLevel): self
    {
        return new self($value, $evidenceLevel);
    }

    /**
     * @param class-string<PersonName>|class-string<RawName>|class-string<Email>|class-string<Iban>|class-string<NationalIdCode>|class-string<OrganisationRegCode> $className
     */
    public static function forType(string $className, ClaimEvidenceLevel $evidenceLevel): self
    {
        return new self($className, $evidenceLevel);
    }
}
