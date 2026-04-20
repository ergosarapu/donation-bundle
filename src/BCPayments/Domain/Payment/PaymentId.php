<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Patchlevel\EventSourcing\Aggregate\RamseyUuidV7Behaviour;

class PaymentId implements AggregateRootId
{
    use RamseyUuidV7Behaviour;
}
