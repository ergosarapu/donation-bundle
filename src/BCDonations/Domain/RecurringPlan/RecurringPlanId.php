<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Patchlevel\EventSourcing\Aggregate\RamseyUuidV7Behaviour;

final class RecurringPlanId implements AggregateRootId
{
    use RamseyUuidV7Behaviour;
}
