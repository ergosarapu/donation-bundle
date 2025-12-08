<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Event;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\RecurringPlanId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\RecurringPlanStatus;
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
