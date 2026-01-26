<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'campaign.created')]
final class CampaignCreated extends AbstractTimestampedEvent implements DomainEventInterface
{
    public readonly CampaignStatus $status;

    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly CampaignId $campaignId,
        public readonly CampaignName $name,
        public readonly CampaignPublicTitle $publicTitle,
    ) {
        parent::__construct($occuredOn);
        $this->status = CampaignStatus::Draft;
    }
}
