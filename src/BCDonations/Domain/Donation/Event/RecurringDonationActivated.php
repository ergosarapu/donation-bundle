<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationStatus;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringInterval;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Event\EventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'recurring_donation.activated')]
class RecurringDonationActivated extends AbstractTimestampedEvent implements EventInterface
{
    public readonly RecurringDonationStatus $status;

    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly RecurringDonationId $id,
        public readonly DateTimeImmutable $nextRenewalTime,
        public readonly RecurringInterval $interval,
    ) {
        parent::__construct($occuredOn);
        $this->status = RecurringDonationStatus::Active;
    }

}
