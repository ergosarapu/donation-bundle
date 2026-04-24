<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;

#[Event(name: 'payment.failed')]
class PaymentFailed extends AbstractTimestampedEvent implements DomainEventInterface
{
    public readonly PaymentStatus $status;

    public function __construct(
        DateTimeImmutable $occuredOn,
        #[DataSubjectId]
        public readonly PaymentId $paymentId,
        public readonly ?string $donationId = null,
        public readonly ?PaymentMethodAction $paymentMethodAction = null,
        #[PersonalData]
        public readonly ?PaymentMethodResult $paymentMethodResult = null,
    ) {
        parent::__construct($occuredOn);
        $this->status = PaymentStatus::Failed;
    }

}
