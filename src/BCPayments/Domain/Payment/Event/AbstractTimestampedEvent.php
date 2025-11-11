<?php

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event;

use DateTimeImmutable;

abstract class AbstractTimestampedEvent
{
    public readonly DateTimeImmutable $occuredOn;

    public function __construct(
    ) {
        $this->occuredOn = new DateTimeImmutable();
    }
}