<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class CampaignName
{
    public function __construct(
        private readonly string $value,
    ) {
    }

    public function toString(): string
    {
        return $this->value;
    }
}
