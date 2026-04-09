<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedApplication\Port\Command;

final class CommandResult
{
    public function __construct(public readonly mixed $result, public readonly string $trackingId)
    {
    }
}
