<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class CampaignName
{
    private readonly string $value;

    public function __construct(string $value)
    {
        /** @var string $value */
        $value = mb_trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('Campaign name cannot be empty.');
        }
        if (mb_strlen($value) > 64) {
            throw new \InvalidArgumentException(sprintf('Campaign name cannot exceed 64 characters, got %d.', strlen($value)));
        }
        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
