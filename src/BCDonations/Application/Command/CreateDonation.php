<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Command;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonorDetails;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ExternalEntityId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;

final class CreateDonation implements CommandInterface
{
    public function __construct(
        public readonly DonationId $donationId,
        public readonly CampaignId $campaignId,
        public readonly ExternalEntityId $paymentId,
        public readonly ?RecurringPlanId $recurringPlanId,
        public readonly Money $amount,
        public readonly ShortDescription $description,
        public readonly DonorDetails $donorDetails,
        public readonly DateTimeImmutable $initiatedAt,
    ) {
    }
}
