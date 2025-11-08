<?php

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;

class PaymentCapturedIntegrationEvent
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly Money $capturedAmount,
        public readonly ?DonationId $donationId,
    ) {
    }
}
