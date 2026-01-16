<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Event\EventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'payment.released_for_gateway_call')]
class PaymentReleasedForGatewayCall extends AbstractTimestampedEvent implements EventInterface
{
    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly PaymentId $paymentId,
    ) {
        parent::__construct($occuredOn);
    }
}
