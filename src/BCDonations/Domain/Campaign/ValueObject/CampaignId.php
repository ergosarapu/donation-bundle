<?php

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Patchlevel\EventSourcing\Aggregate\RamseyUuidV7Behaviour;

final class CampaignId implements AggregateRootId
{
    use RamseyUuidV7Behaviour;
}
