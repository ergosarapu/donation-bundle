<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Command;

use ErgoSarapu\DonationBundle\BCIdentities\Domain\EntityClaim\EntityClaimId;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\EntityClaim\EntityClaimSource;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;

final class ClaimIdentity implements CommandInterface
{
    public function __construct(
        public readonly EntityClaimId $entityClaimId,
        public readonly EntityClaimSource $source,
        public readonly ?string $name,
        public readonly ?Email $email,
        public readonly ?Iban $iban,
        public readonly ?NationalIdCode $nationalIdCode,
    ) {
    }
}
