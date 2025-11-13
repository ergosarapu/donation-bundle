<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationStatus;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;

abstract class AbstractDonationCreated extends AbstractTimestampedEvent
{
    public function __construct(
        public readonly DonationId $donationId,
        public readonly Money $amount,
        public readonly DonationStatus $status,
        public readonly ?PersonName $donorName,
        public readonly ?Email $donorEmail,
        public readonly ?NationalIdCode $donorNationalIdCode,
    ) {
        parent::__construct();
    }
}
