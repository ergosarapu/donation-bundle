<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Port;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;

interface DonationRepositoryInterface
{
    public function save(Donation $donation): void;

    public function load(DonationId $donationId): Donation;

    public function has(DonationId $donationId): bool;
}
