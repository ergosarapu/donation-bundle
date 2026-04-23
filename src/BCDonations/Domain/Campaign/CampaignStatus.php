<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign;

enum CampaignStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
