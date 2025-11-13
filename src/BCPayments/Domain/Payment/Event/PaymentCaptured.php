<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\ValueObject\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'payment.captured')]
class PaymentCaptured extends AbstractTimestampedEvent
{
    public readonly PaymentStatus $status;

    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly Money $capturedAmount,
        public readonly ?PaymentAppliedToId $appliedTo = null,
    ) {
        parent::__construct();
        $this->status = PaymentStatus::Captured;
    }

}
