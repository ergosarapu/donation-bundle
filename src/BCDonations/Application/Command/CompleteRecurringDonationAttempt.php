<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Command;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationStatus;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringToken;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;

final class CompleteRecurringDonationAttempt implements CommandInterface
{
    public function __construct(
        public readonly RecurringPlanId $recurringPlanId,
        public readonly DonationId $donationId,
        public readonly DonationStatus $donationStatus,
        public readonly ?RecurringToken $recurringToken = null,
        public readonly bool $temporalFailure = false,
    ) {
    }
}
