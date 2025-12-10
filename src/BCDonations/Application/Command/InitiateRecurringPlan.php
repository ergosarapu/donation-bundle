<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Command;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationRequest;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringInterval;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;

final class InitiateRecurringPlan implements CommandInterface
{
    public readonly RecurringPlanId $recurringPlanId;

    public readonly DonationRequest $donationRequest;

    public function __construct(
        public readonly RecurringInterval $interval,
        DonationRequest $donationRequest,
    ) {
        $this->recurringPlanId = RecurringPlanId::generate();
        $this->donationRequest = $donationRequest;
    }
}
