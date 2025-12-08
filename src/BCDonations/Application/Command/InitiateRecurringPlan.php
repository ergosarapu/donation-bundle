<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Command;

use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\RecurringPlanId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\RecurringInterval;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;

final class InitiateRecurringPlan implements CommandInterface
{
    public readonly RecurringPlanId $recurringPlanId;

    public function __construct(
        public readonly Money $amount,
        public readonly CampaignId $campaignId,
        public readonly Gateway $gateway,
        public readonly RecurringInterval $interval,
        public readonly Email $donorEmail,
        public readonly ?PersonName $donorName = null,
        public readonly ?NationalIdCode $donorNationalIdCode = null,
    ) {
        $this->recurringPlanId = RecurringPlanId::generate();
    }
}
