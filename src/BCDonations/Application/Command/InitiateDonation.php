<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Command;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;

final class InitiateDonation implements CommandInterface
{
    public function __construct(
        public readonly DonationId $donationId,
        public readonly Money $amount,
        public readonly CampaignId $campaignId,
        public readonly Gateway $gateway,
        public readonly bool $recurringActivation = false,
        public readonly ?RecurringPlanId $recurringPlanId = null,
        public readonly ?PersonName $donorName = null,
        public readonly ?Email $donorEmail = null,
        public readonly ?NationalIdCode $donorNationalIdCode = null,
        public readonly ?DonationId $parentRecurringActivationDonationId = null,
    ) {
    }
}
