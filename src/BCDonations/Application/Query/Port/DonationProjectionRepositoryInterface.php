<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationStatus;

interface DonationProjectionRepositoryInterface
{
    public function findOne(?DonationId $id = null, ?DonationStatus $status = null): ?Donation;
}
