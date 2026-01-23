<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Donations\Command;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationRequest;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringInterval;
use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationCommandInterface;

final class InitiateDonationIntegrationCommand implements IntegrationCommandInterface
{
    public function __construct(
        public readonly DonationRequest $donationRequest,
        public readonly ?RecurringInterval $recurringInterval = null,
    ) {
    }
}
