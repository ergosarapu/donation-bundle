<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Event;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\RecurringPlanId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Event\EventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'recurring_plan.renewal_completed')]
class RecurringPlanRenewalCompleted extends AbstractTimestampedEvent implements EventInterface
{
    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly RecurringPlanId $id,
        public readonly DateTimeImmutable $nextRenewalTime,
    ) {
        parent::__construct($occuredOn);
    }

}
