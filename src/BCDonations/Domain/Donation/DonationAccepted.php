<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'donation.accepted')]
final class DonationAccepted extends AbstractTimestampedEvent implements DomainEventInterface
{
    public readonly DonationStatus $status;

    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly DateTimeImmutable $acceptedAt,
        public readonly DonationId $donationId,
        public readonly Money $acceptedAmount,
        public readonly string $paymentId,
        public readonly ?RecurringPlanId $recurringPlanId = null,
    ) {
        parent::__construct($occuredOn);
        $this->status = DonationStatus::Accepted;
    }
}
