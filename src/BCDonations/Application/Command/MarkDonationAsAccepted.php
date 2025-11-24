<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Command;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;

final class MarkDonationAsAccepted implements CommandInterface
{
    public function __construct(
        public readonly DonationId $donationId,
        public readonly Money $acceptedAmount,
    ) {
    }
}
