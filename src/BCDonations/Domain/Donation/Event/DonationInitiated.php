<?php

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\ValueObject\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationStatus;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'donation.initiated')]
final class DonationInitiated extends AbstractDonationCreated
{
    public function __construct(
        DonationId $donationId,
        Money $amount,
        DonationStatus $status,
        public readonly CampaignId $campaignId,
        public readonly PaymentId $paymentId,
        public readonly Gateway $gateway,
        public readonly ShortDescription $description,
        ?PersonName $donorName,
        ?Email $donorEmail,
        ?NationalIdCode $donorNationalIdCode,
    ) {
        parent::__construct(
            $donationId,
            $amount,
            $status,
            $donorName,
            $donorEmail,
            $donorNationalIdCode,
        );
    }
}
