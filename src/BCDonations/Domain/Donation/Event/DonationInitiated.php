<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\ValueObject\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationStatus;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Event\EventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'donation.initiated')]
final class DonationInitiated extends AbstractTimestampedEvent implements EventInterface
{
    public function __construct(
        public readonly DonationId $donationId,
        public readonly Money $amount,
        public readonly DonationStatus $status,
        public readonly CampaignId $campaignId,
        public readonly PaymentId $paymentId,
        public readonly Gateway $gateway,
        public readonly ShortDescription $description,
        public readonly bool $recurringActivation,
        public readonly ?RecurringDonationId $recurringDonationId,
        public readonly ?PersonName $donorName,
        public readonly ?Email $donorEmail,
        public readonly ?NationalIdCode $donorNationalIdCode,
        public readonly ?DonationId $parentRecurringActivationDonationId = null,
    ) {
        parent::__construct();
    }
}
