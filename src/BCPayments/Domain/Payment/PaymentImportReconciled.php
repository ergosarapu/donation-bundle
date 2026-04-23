<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'payment.import_reconciled')]
class PaymentImportReconciled extends AbstractTimestampedEvent implements DomainEventInterface
{
    public readonly PaymentImportStatus $importStatus;

    public function __construct(
        DateTimeImmutable $occurredOn,
        public readonly PaymentId $paymentId,
        public readonly PaymentId $reconciledWith,
    ) {
        parent::__construct($occurredOn);
        $this->importStatus = PaymentImportStatus::Reconciled;
    }

}
