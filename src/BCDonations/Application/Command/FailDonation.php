<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Command;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;

final class FailDonation implements CommandInterface
{
    public function __construct(
        public readonly DonationId $donationId,
        public readonly string $paymentId,
    ) {
    }
}
