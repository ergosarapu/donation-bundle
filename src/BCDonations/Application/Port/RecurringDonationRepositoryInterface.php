<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Port;

use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\RecurringDonation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationId;

interface RecurringDonationRepositoryInterface
{
    public function save(RecurringDonation $donation): void;

    public function load(RecurringDonationId $donationId): RecurringDonation;

    public function has(RecurringDonationId $donationId): bool;
}
