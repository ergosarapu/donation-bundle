<?php

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\ValueObject\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'payment.captured')]
class PaymentCaptured
{
    public readonly PaymentStatus $status;

    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly Money $capturedAmount,
        public readonly ?DonationId $donationId = null,
    ) {
        $this->status = PaymentStatus::Captured;
    }

}
