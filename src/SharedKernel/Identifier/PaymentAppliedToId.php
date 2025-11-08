<?php

namespace ErgoSarapu\DonationBundle\SharedKernel\Identifier;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Patchlevel\EventSourcing\Aggregate\RamseyUuidV7Behaviour;

final class PaymentAppliedToId implements AggregateRootId
{
    use RamseyUuidV7Behaviour;
}
