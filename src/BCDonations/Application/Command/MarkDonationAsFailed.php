<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Command;

use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;

final class MarkDonationAsFailed implements CommandInterface
{
    public function __construct(
        public readonly DonationId $donationId
    ) {
    }
}
