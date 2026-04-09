<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Command;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonorDetails;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ExternalEntityId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Interval;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;

final class CreateRecurringPlan implements CommandInterface
{
    public function __construct(
        public readonly RecurringPlanId $recurringPlanId,
        public readonly RecurringPlanStatus $status,
        public readonly Interval $interval,
        public readonly DonationId $initialDonationId,
        public readonly CampaignId $campaignId,
        public readonly ExternalEntityId $paymentMethodId,
        public readonly Money $amount,
        public readonly Gateway $gateway,
        public readonly DonorDetails $donorDetails,
        public readonly ShortDescription $description,
        public readonly ?DateTimeImmutable $nextRenewalTime,
        public readonly DateTimeImmutable $initiatedAt,
    ) {
    }
}
