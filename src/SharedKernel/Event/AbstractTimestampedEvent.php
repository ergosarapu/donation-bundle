<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\Event;

use DateTimeImmutable;
use Patchlevel\Hydrator\Normalizer\DateTimeImmutableNormalizer;

abstract class AbstractTimestampedEvent
{
    #[DateTimeImmutableNormalizer(DateTimeImmutable::RFC3339_EXTENDED)]
    public readonly DateTimeImmutable $occuredOn;

    public function __construct(
        DateTimeImmutable $occuredOn
    ) {
        $this->occuredOn = $occuredOn;
    }
}
