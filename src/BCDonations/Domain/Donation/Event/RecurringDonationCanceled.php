<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Event\EventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'recurring_donation.canceled')]
class RecurringDonationCanceled extends AbstractTimestampedEvent implements EventInterface
{
    public readonly RecurringDonationStatus $status;

    public function __construct(
        DateTimeImmutable $occurredOn,
        public readonly RecurringDonationId $id,
    ) {
        parent::__construct($occurredOn);
        $this->status = RecurringDonationStatus::Canceled;
    }

}
