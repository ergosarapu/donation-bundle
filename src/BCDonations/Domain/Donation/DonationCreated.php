<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;

#[Event(name: 'donation.created')]
final class DonationCreated extends AbstractTimestampedEvent implements DomainEventInterface
{
    public readonly DonationStatus $status;

    public function __construct(
        DateTimeImmutable $occuredOn,
        #[DataSubjectId]
        public readonly DonationId $donationId,
        public readonly Money $amount,
        public readonly CampaignId $campaignId,
        public readonly string $paymentId,
        #[PersonalData]
        public readonly ?ShortDescription $description,
        #[PersonalData]
        public readonly ?DonorDetails $donorDetails,
        public readonly ?RecurringPlanId $recurringPlanId,
        public readonly DateTimeImmutable $initiatedAt,
    ) {
        parent::__construct($occuredOn);
        $this->status = DonationStatus::Created;
    }
}
