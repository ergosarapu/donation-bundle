<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Event;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\RecurringPlanId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\RecurringPlanStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Event\EventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'recurring_plan.expired')]
class RecurringPlanExpired extends AbstractTimestampedEvent implements EventInterface
{
    public readonly RecurringPlanStatus $status;

    public function __construct(
        DateTimeImmutable $occurredOn,
        public readonly RecurringPlanId $id,
    ) {
        parent::__construct($occurredOn);
        $this->status = RecurringPlanStatus::Expired;
    }

}
