<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'payment.succeeded')]
class PaymentSucceeded extends AbstractTimestampedEvent implements DomainEventInterface
{
    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly PaymentId $paymentId,
        public readonly Money $amount,
        public readonly ?string $donationId = null,
    ) {
        parent::__construct($occuredOn);
    }

}
