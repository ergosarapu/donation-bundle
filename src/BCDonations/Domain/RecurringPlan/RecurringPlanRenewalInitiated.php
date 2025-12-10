<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonorIdentity;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Event\EventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'recurring_plan.renewal_initiated')]
class RecurringPlanRenewalInitiated extends AbstractTimestampedEvent implements EventInterface
{
    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly RecurringPlanId $recurringPlanId,
        public readonly DonationId $renewalDonationId,
        public readonly CampaignId $campaignId,
        public readonly Money $amount,
        public readonly Gateway $gateway,
        public readonly DonorIdentity $donorIdentity,
        public readonly RecurringToken $recurringToken,
    ) {
        parent::__construct($occuredOn);
    }
}
