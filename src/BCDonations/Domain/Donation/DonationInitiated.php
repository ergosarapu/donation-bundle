<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanAction;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;

#[Event(name: 'donation.initiated')]
final class DonationInitiated extends AbstractTimestampedEvent implements DomainEventInterface
{
    public readonly DonationStatus $status;

    public function __construct(
        DateTimeImmutable $occuredOn,
        #[DataSubjectId]
        public readonly DonationId $donationId,
        public readonly Money $amount,
        public readonly CampaignId $campaignId,
        public readonly PaymentId $paymentId,
        public readonly Gateway $gateway,
        public readonly ShortDescription $description,
        public readonly ?RecurringPlanId $recurringPlanId,
        public readonly ?RecurringPlanAction $recurringPlanAction,
        #[PersonalData]
        public readonly ?DonorDetails $donorDetails,
    ) {
        parent::__construct($occuredOn);
        $this->status = DonationStatus::Initiated;
    }
}
