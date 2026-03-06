<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Command;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationRequest;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanAction;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;

final class InitiateDonation implements CommandInterface
{
    public function __construct(
        public readonly DonationRequest $donationRequest,
        public readonly ?RecurringPlanId $recurringPlanId = null,
        public readonly ?RecurringPlanAction $recurringPlanAction = null,
    ) {
    }
}
