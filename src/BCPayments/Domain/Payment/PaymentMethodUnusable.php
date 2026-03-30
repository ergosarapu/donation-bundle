<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ExternalEntityId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'payment_method.unusable')]
class PaymentMethodUnusable extends AbstractTimestampedEvent implements DomainEventInterface
{
    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly PaymentMethodId $paymentMethodId,
        public readonly PaymentMethodUnusableReason $reason,
        public readonly ExternalEntityId $createdFor,
    ) {
        parent::__construct($occuredOn);
    }
}
