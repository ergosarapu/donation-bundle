<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ExternalEntityId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;

#[Event(name: 'payment.captured')]
class PaymentCaptured extends AbstractTimestampedEvent implements DomainEventInterface
{
    public readonly PaymentStatus $status;

    public function __construct(
        DateTimeImmutable $occuredOn,
        #[DataSubjectId]
        public readonly PaymentId $paymentId,
        public readonly Money $capturedAmount,
        public readonly ?ExternalEntityId $appliedTo = null,
        public readonly ?PaymentMethodAction $paymentMethodAction = null,
        #[PersonalData]
        public readonly ?PaymentMethodResult $paymentMethodResult = null,
        #[PersonalData]
        public readonly ?Iban $iban = null,
    ) {
        parent::__construct($occuredOn);
        $this->status = PaymentStatus::Captured;
    }

}
