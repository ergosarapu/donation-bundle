<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Port;

use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\RepositoryInterface;

/**
 * @extends RepositoryInterface<RecurringPlan, RecurringPlanId>
 */
interface RecurringPlanRepositoryInterface extends RepositoryInterface
{
}
