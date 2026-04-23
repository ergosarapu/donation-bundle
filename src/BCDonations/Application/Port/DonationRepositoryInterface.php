<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Port;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\RepositoryInterface;

/**
 * @extends RepositoryInterface<Donation, DonationId>
 */
interface DonationRepositoryInterface extends RepositoryInterface
{
}
