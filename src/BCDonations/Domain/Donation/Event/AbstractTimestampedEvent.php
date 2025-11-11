<?php

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event;

use DateTimeImmutable;
use Patchlevel\Hydrator\Normalizer\DateTimeImmutableNormalizer;

abstract class AbstractTimestampedEvent
{
    #[DateTimeImmutableNormalizer(DateTimeImmutable::RFC3339_EXTENDED)]
    public readonly DateTimeImmutable $occuredOn;

    public function __construct(
    ) {
        $this->occuredOn = new DateTimeImmutable();
    }
}
