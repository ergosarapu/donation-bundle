<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedApplication\Message;

use DateTimeImmutable;

class DelayedMessage
{
    public function __construct(
        public readonly object $message,
        public readonly DateTimeImmutable $delayUntil,
    ) {
    }
}
