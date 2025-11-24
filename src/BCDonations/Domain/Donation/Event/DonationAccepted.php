<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationStatus;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Event\EventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'donation.accepted')]
final class DonationAccepted extends AbstractTimestampedEvent implements EventInterface
{
    public readonly DonationStatus $status;

    public function __construct(
        public readonly DonationId $donationId,
        public readonly Money $acceptedAmount,
        public readonly bool $activatesRecurring = false,
        public readonly ?RecurringDonationId $recurringDonationId = null,
    ) {
        parent::__construct();
        $this->status = DonationStatus::Accepted;
    }
}
