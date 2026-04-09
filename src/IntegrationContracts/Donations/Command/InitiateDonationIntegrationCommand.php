<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Donations\Command;

use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationCommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Interval;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;

final class InitiateDonationIntegrationCommand implements IntegrationCommandInterface
{
    public function __construct(
        public readonly string $donationId,
        public readonly string $campaignId,
        public readonly Money $amount,
        public readonly Gateway $gateway,
        public readonly ShortDescription $description,
        public readonly ?Email $donorEmail = null,
        public readonly ?PersonName $donorName = null,
        public readonly ?NationalIdCode $donorNationalIdCode = null,
        public readonly ?Interval $recurringInterval = null,
    ) {
    }
}
