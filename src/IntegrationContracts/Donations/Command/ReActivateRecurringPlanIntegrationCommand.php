<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Donations\Command;

use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationCommandInterface;

final class ReActivateRecurringPlanIntegrationCommand implements IntegrationCommandInterface
{
    public function __construct(
        public readonly string $recurringPlanId,
    ) {
    }
}
