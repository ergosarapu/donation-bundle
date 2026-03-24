<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity;

final class MergeAttempt
{
    private function __construct(
        public readonly bool $hasConflict,
        public readonly ?object $event = null,
    ) {
    }

    public static function noChange(): self
    {
        return new self(false);
    }

    public static function changed(object $event): self
    {
        return new self(false, $event);
    }

    public static function conflict(): self
    {
        return new self(true);
    }
}
