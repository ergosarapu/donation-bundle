<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonorIdentity;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'recurring_plan.initiated')]
class RecurringPlanInitiated extends AbstractTimestampedEvent implements DomainEventInterface
{
    public readonly RecurringPlanStatus $status;

    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly RecurringPlanAction $recurringPlanAction,
        public readonly DonationId $initialDonationId,
        public readonly CampaignId $campaignId,
        public readonly Money $amount,
        public readonly RecurringInterval $interval,
        public readonly Gateway $gateway,
        public readonly DonorIdentity $donorIdentity,
    ) {
        parent::__construct($occuredOn);
        $this->status = RecurringPlanStatus::Pending;
    }
}
