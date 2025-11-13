<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\ValueObject\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\URL;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'payment.initiated')]
class PaymentInitiated extends AbstractTimestampedEvent
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly Money $amount,
        public readonly PaymentStatus $status,
        public readonly Gateway $gateway,
        public readonly ShortDescription $description,
        public readonly URL $redirectUrl,
        public readonly ?PaymentAppliedToId $appliedTo = null,
    ) {
        parent::__construct();
    }
}
