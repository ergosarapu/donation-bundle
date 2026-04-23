<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Patchlevel\EventSourcing\Aggregate\RamseyUuidV7Behaviour;

final class IdentityId implements AggregateRootId
{
    use RamseyUuidV7Behaviour;
}
