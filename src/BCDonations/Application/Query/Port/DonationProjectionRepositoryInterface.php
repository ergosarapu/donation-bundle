<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationStatus;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationId;

interface DonationProjectionRepositoryInterface
{
    public function findOneBy(?DonationId $id = null, ?DonationStatus $status = null, ?RecurringDonationId $recurringDonationId = null): ?Donation;

    /**
     * @return array<Donation>
     */
    public function findBy(?DonationId $id = null, ?DonationStatus $status = null, ?RecurringDonationId $recurringDonationId = null): array;
}
