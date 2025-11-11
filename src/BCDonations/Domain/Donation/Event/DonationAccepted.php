<?php

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationStatus;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'donation.accepted')]
final class DonationAccepted extends AbstractTimestampedEvent
{
    public readonly DonationStatus $status;

    public function __construct(
        public readonly DonationId $donationId,
        public readonly Money $acceptedAmount,
    ) {
        parent::__construct();
        $this->status = DonationStatus::Accepted;
    }
}
