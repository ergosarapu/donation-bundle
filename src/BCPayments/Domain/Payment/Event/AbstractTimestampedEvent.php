<?php

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event;

use DateTimeImmutable;
use DateTimeZone;
use Patchlevel\Hydrator\Normalizer\DateTimeImmutableNormalizer;

abstract class AbstractTimestampedEvent
{
    #[DateTimeImmutableNormalizer]
    public readonly DateTimeImmutable $occuredOn;

    public function __construct(
    ) {
        $this->occuredOn = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}