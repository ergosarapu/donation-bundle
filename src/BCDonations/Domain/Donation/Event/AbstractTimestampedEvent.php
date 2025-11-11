<?php

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event;

use DateTimeImmutable;

abstract class AbstractTimestampedEvent
{
    public readonly DateTimeImmutable $occuredOn;

    public function __construct(
    ) {
        $this->occuredOn = new DateTimeImmutable();
    }
}
