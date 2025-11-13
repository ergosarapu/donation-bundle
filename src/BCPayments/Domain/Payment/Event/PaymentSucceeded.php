<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event;

use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'payment.succeeded')]
class PaymentSucceeded extends AbstractTimestampedEvent
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly Money $amount,
        public readonly ?PaymentAppliedToId $appliedTo = null,
    ) {
        parent::__construct();
    }

}
