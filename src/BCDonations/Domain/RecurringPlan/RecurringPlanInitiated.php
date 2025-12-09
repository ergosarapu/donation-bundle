<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Event\EventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'recurring_plan.initiated')]
class RecurringPlanInitiated extends AbstractTimestampedEvent implements EventInterface
{
    public readonly RecurringPlanStatus $status;

    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly RecurringPlanId $id,
        public readonly DonationId $activationDonationId,
        public readonly CampaignId $campaignId,
        public readonly Money $amount,
        public readonly RecurringInterval $interval,
        public readonly Email $donorEmail,
        public readonly Gateway $gateway,
        public readonly ?PersonName $donorName = null,
        public readonly ?NationalIdCode $donorNationalIdCode = null,
    ) {
        parent::__construct($occuredOn);
        $this->status = RecurringPlanStatus::Pending;
    }
}
