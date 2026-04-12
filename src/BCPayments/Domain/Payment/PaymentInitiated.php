<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;

#[Event(name: 'payment.initiated')]
class PaymentInitiated extends AbstractTimestampedEvent implements DomainEventInterface
{
    public readonly PaymentStatus $status;

    public function __construct(
        DateTimeImmutable $occuredOn,
        #[DataSubjectId]
        public readonly PaymentId $paymentId,
        public readonly Money $amount,
        public readonly Gateway $gateway,
        public readonly ShortDescription $description,
        public readonly string $appliedTo,
        #[PersonalData]
        public readonly ?Email $email,
        public readonly ?PaymentMethodAction $paymentMethodAction = null,
    ) {
        parent::__construct($occuredOn);
        $this->status = PaymentStatus::Initiated;
    }
}
