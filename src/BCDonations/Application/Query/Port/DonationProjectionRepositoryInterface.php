<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationStatus;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;

interface DonationProjectionRepositoryInterface
{
    public function findOneBy(?DonationId $id = null, ?DonationStatus $status = null, ?RecurringPlanId $recurringPlanId = null): ?Donation;

    /**
     * @return array<Donation>
     */
    public function findBy(?DonationId $id = null, ?DonationStatus $status = null, ?RecurringPlanId $recurringPlanId = null): array;
}
