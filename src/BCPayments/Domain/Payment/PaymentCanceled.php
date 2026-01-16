<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'payment.canceled')]
class PaymentCanceled extends AbstractTimestampedEvent
{
    public readonly PaymentStatus $status;

    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly PaymentId $paymentId,
    ) {
        parent::__construct($occuredOn);
        $this->status = PaymentStatus::Canceled;
    }

}
