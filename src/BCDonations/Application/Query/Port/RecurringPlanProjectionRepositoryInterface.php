<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\RecurringPlanId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\RecurringPlanStatus;

interface RecurringPlanProjectionRepositoryInterface
{
    public function findOne(?RecurringPlanId $id = null, ?RecurringPlanStatus $status = null): ?RecurringPlan;
}
