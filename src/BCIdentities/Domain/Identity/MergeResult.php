<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity;

final class MergeResult
{
    private function __construct(
        private readonly bool $conflict,
    ) {
    }

    public static function success(): self
    {
        return new self(false);
    }

    public static function conflict(): self
    {
        return new self(true);
    }

    public function isSuccess(): bool
    {
        return !$this->conflict;
    }

    public function isConflict(): bool
    {
        return $this->conflict;
    }

}
