<?php

namespace ErgoSarapu\DonationBundle\SharedKernel\Identifier;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Patchlevel\EventSourcing\Aggregate\RamseyUuidV7Behaviour;

class PaymentId implements AggregateRootId
{
    use RamseyUuidV7Behaviour; 
}
