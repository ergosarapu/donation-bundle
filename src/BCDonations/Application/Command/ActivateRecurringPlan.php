<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Command;

use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ExternalEntityId;

final class ActivateRecurringPlan implements CommandInterface
{
    public function __construct(
        public readonly RecurringPlanId $recurringPlanId,
        public readonly ExternalEntityId $paymentMethodId,
    ) {
    }
}
