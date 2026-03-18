<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Command;

use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationCommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;

class ClaimIdentityIntegrationCommand implements IntegrationCommandInterface
{
    /**
     * @param string $claimId  Source entity ID (becomes the claim ID for straightforward linking between BCs)
     * @param string $source   Bounded context that emitted the claim (e.g. "Payments")
     * @param string|null $name Raw name provided by the source
     * @param Email|null $email Email address from claim
     * @param Iban|null $iban   Bank account number
     * @param NationalIdCode|null $nationalIdCode National identifier (e.g. Estonian personal code)
     */
    public function __construct(
        public readonly string $claimId,
        public readonly string $source,
        public readonly ?string $name = null,
        public readonly ?Email $email = null,
        public readonly ?Iban $iban = null,
        public readonly ?NationalIdCode $nationalIdCode = null,
    ) {
    }
}
