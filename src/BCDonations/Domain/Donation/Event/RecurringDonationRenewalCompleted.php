<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Event\EventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'recurring_donation.renewal_completed')]
class RecurringDonationRenewalCompleted extends AbstractTimestampedEvent implements EventInterface
{
    public function __construct(
        public readonly RecurringDonationId $id,
        public readonly DateTimeImmutable $nextRenewalTime,
    ) {
        parent::__construct();
    }

}
