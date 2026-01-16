<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanStatus;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodlId;

interface RecurringPlanProjectionRepositoryInterface
{
    public function findOne(?RecurringPlanId $id = null, ?RecurringPlanStatus $status = null, ?PaymentMethodlId $paymentMethodId = null): ?RecurringPlan;
}
