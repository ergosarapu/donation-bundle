<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'payment.method_use_rejected')]
class PaymentMethodUseRejected extends AbstractTimestampedEvent
{
    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly PaymentMethodAction $paymentMethodAction,
    ) {
        parent::__construct($occuredOn);
    }
}
