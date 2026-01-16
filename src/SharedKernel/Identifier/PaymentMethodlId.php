<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\Identifier;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Patchlevel\EventSourcing\Aggregate\RamseyUuidV7Behaviour;

final class PaymentMethodlId implements AggregateRootId
{
    use RamseyUuidV7Behaviour;
}
