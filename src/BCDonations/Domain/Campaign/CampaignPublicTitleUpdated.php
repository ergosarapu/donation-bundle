<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'campaign.public_title_updated')]
final class CampaignPublicTitleUpdated extends AbstractTimestampedEvent implements DomainEventInterface
{
    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly CampaignId $campaignId,
        public readonly CampaignPublicTitle $publicTitle,
    ) {
        parent::__construct($occuredOn);
    }
}
