<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringToken;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Event\EventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'donation.initiated')]
final class DonationInitiated extends AbstractTimestampedEvent implements EventInterface
{
    public readonly DonationStatus $status;

    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly DonationId $donationId,
        public readonly Money $amount,
        public readonly CampaignId $campaignId,
        public readonly PaymentId $paymentId,
        public readonly Gateway $gateway,
        public readonly ShortDescription $description,
        public readonly ?RecurringPlanId $recurringPlanId,
        public readonly ?RecurringToken $recurringToken,
        public readonly DonorIdentity $donorIdentity,
    ) {
        parent::__construct($occuredOn);
        $this->status = DonationStatus::Pending;

    }
}
