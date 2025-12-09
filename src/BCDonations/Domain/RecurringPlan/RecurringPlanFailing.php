<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Event\EventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'recurring_plan.failing')]
class RecurringPlanFailing extends AbstractTimestampedEvent implements EventInterface
{
    public readonly RecurringPlanStatus $status;

    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly RecurringPlanId $id,
    ) {
        parent::__construct($occuredOn);
        $this->status = RecurringPlanStatus::Failing;
    }

}
