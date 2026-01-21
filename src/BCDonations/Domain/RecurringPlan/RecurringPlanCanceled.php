<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'recurring_plan.canceled')]
class RecurringPlanCanceled extends AbstractTimestampedEvent implements DomainEventInterface
{
    public readonly RecurringPlanStatus $status;

    public function __construct(
        DateTimeImmutable $occurredOn,
        public readonly RecurringPlanId $id,
    ) {
        parent::__construct($occurredOn);
        $this->status = RecurringPlanStatus::Canceled;
    }

}
