<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use DateInterval;
use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class Interval
{
    public const Monthly = 'P1M';
    public const Yearly = 'P1Y';
    public const Weekly = 'P1W';
    public const Daily = 'P1D';
    public const Quarterly = 'P3M';

    public function __construct(
        private readonly string $interval
    ) {
        new DateInterval($interval);
    }

    public function toDateInterval(): DateInterval
    {
        return new DateInterval($this->interval);
    }

    public function toString(): string
    {
        return $this->interval;
    }
}
