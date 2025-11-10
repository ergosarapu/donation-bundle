<?php

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationStatus;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'donation.failed')]
final class DonationFailed
{
    public readonly DonationStatus $status;

    public function __construct(
        public readonly DonationId $donationId,
    ) {
        $this->status = DonationStatus::Failed;
    }
}
