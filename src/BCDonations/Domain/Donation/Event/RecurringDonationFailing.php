<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event;

use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Event\EventInterface;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'recurring_donation.failing')]
class RecurringDonationFailing implements EventInterface
{
    public readonly RecurringDonationStatus $status;

    public function __construct(
        public readonly RecurringDonationId $id,
    ) {
        $this->status = RecurringDonationStatus::Failing;
    }

}
