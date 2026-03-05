<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedApplication\Query\Model;

use DateTimeImmutable;

class CommandStatus
{
    public function __construct(
        public string $correlationId,
        public DateTimeImmutable $appliedAt
    ) {
    }
}
