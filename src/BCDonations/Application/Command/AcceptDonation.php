<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Command;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;

final class AcceptDonation implements CommandInterface
{
    public function __construct(
        public readonly DonationId $donationId,
        public readonly Money $amount,
    ) {
    }
}
