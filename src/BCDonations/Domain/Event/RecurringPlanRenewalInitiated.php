<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Event;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\RecurringPlanId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Event\EventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'recurring_plan.renewal_initiated')]
class RecurringPlanRenewalInitiated extends AbstractTimestampedEvent implements EventInterface
{
    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly RecurringPlanId $recurringPlanId,
        public readonly DonationId $activationDonationId,
        public readonly CampaignId $campaignId,
        public readonly Money $amount,
        public readonly Gateway $gateway,
        public readonly Email $donorEmail,
    ) {
        parent::__construct($occuredOn);
    }
}
