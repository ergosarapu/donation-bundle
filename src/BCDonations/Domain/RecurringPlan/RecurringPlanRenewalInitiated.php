<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonorDetails;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;

#[Event(name: 'recurring_plan.renewal_initiated')]
class RecurringPlanRenewalInitiated extends AbstractTimestampedEvent implements DomainEventInterface
{
    public function __construct(
        DateTimeImmutable $occuredOn,
        #[DataSubjectId]
        public readonly RecurringPlanId $recurringPlanId,
        public readonly RecurringPlanAction $recurringPlanAction,
        public readonly DonationId $renewalDonationId,
        public readonly CampaignId $campaignId,
        public readonly Money $amount,
        public readonly Gateway $gateway,
        #[PersonalData]
        public readonly ?DonorDetails $donorDetails,
        public readonly ShortDescription $description,
    ) {
        parent::__construct($occuredOn);
    }
}
