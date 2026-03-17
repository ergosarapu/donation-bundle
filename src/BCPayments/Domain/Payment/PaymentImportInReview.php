<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'payment.import_in_review')]
class PaymentImportInReview extends AbstractTimestampedEvent implements DomainEventInterface
{
    public readonly PaymentImportStatus $importStatus;

    public function __construct(
        DateTimeImmutable $occurredOn,
        public readonly PaymentId $paymentId,
    ) {
        parent::__construct($occurredOn);
        $this->importStatus = PaymentImportStatus::Review;
    }

}
