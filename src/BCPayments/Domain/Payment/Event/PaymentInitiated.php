<?php

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\ValueObject\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\URL;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'payment.initiated')]
class PaymentInitiated extends AbstractPaymentCreated
{
    public function __construct(
        PaymentId $paymentId,
        Money $amount,
        public readonly PaymentStatus $status,
        public readonly Gateway $gateway,
        public readonly ShortDescription $description,
        public readonly URL $redirectUrl,
        public readonly ?DonationId $donationId = null,
    ) {
        parent::__construct(
            $paymentId,
            $amount
        );
    }
}
