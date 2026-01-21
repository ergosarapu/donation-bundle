<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'recurring_plan.activated')]
class RecurringPlanActivated extends AbstractTimestampedEvent implements DomainEventInterface
{
    public readonly RecurringPlanStatus $status;

    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly RecurringPlanId $id,
        public readonly DateTimeImmutable $nextRenewalTime,
        public readonly RecurringInterval $interval,
    ) {
        parent::__construct($occuredOn);
        $this->status = RecurringPlanStatus::Active;
    }

}
