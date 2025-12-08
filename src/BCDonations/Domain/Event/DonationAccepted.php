<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Event;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\DonationStatus;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\RecurringPlanId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Event\EventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'donation.accepted')]
final class DonationAccepted extends AbstractTimestampedEvent implements EventInterface
{
    public readonly DonationStatus $status;

    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly DonationId $donationId,
        public readonly Money $acceptedAmount,
        public readonly bool $activatesRecurring = false,
        public readonly ?RecurringPlanId $recurringPlanId = null,
    ) {
        parent::__construct($occuredOn);
        $this->status = DonationStatus::Accepted;
    }
}
