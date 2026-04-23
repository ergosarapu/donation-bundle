<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'recurring_plan.renewal_completed')]
class RecurringPlanRenewalCompleted extends AbstractTimestampedEvent implements DomainEventInterface
{
    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly RecurringPlanId $id,
        public readonly DateTimeImmutable $nextRenewalTime,
    ) {
        parent::__construct($occuredOn);
    }

}
