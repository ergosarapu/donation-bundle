<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;

#[Event(name: 'payment.authorized')]
class PaymentAuthorized extends AbstractTimestampedEvent implements DomainEventInterface
{
    public readonly PaymentStatus $status;

    public function __construct(
        DateTimeImmutable $occurredOn,
        #[DataSubjectId]
        public readonly PaymentId $paymentId,
        public readonly Money $authorizedAmount,
        public readonly ?PaymentAppliedToId $appliedTo = null,
        public readonly ?PaymentMethodAction $paymentMethodAction = null,
        #[PersonalData]
        public readonly ?PaymentMethodResult $paymentMethodResult = null,
    ) {
        parent::__construct($occurredOn);
        $this->status = PaymentStatus::Authorized;
    }

}
