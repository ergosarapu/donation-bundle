<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'payment.did_not_succeed')]
class PaymentDidNotSucceed extends AbstractTimestampedEvent
{
    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly PaymentId $paymentId,
        public readonly ?PaymentAppliedToId $appliedTo = null,
    ) {
        parent::__construct($occuredOn);

    }

}
