<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'campaign.donation_description_updated')]
final class CampaignDonationDescriptionUpdated extends AbstractTimestampedEvent implements DomainEventInterface
{
    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly CampaignId $campaignId,
        public readonly ShortDescription $donationDescription,
    ) {
        parent::__construct($occuredOn);
    }
}
