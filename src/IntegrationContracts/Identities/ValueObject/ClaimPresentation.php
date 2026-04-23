<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\LegalIdentifier;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\RawName;

final class ClaimPresentation
{
    /**
     * @param PersonName|RawName|Email|Iban|LegalIdentifier|string $value
     */
    public function __construct(
        public readonly mixed $value,
        public readonly ClaimEvidenceLevel $evidenceLevel,
    ) {
    }

    /**
     * @param PersonName|RawName|Email|Iban|LegalIdentifier $value
     */
    public static function forValue(object $value, ClaimEvidenceLevel $evidenceLevel): self
    {
        return new self($value, $evidenceLevel);
    }

    /**
     * @param string $className
     */
    public static function forType(string $className, ClaimEvidenceLevel $evidenceLevel): self
    {
        return new self($className, $evidenceLevel);
    }
}
