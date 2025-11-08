<?php

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Patchlevel\EventSourcing\Aggregate\RamseyUuidV7Behaviour;

final class DonationId implements AggregateRootId
{
    use RamseyUuidV7Behaviour;
}
