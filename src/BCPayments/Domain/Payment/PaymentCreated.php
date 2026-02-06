<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event(name: 'payment.created')]
class PaymentCreated extends AbstractTimestampedEvent implements DomainEventInterface
{
    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly DateTimeImmutable $initiatedAt,
        public readonly ?DateTimeImmutable $capturedAt,
        public readonly PaymentId $paymentId,
        public readonly PaymentStatus $status,
        public readonly Money $amount,
        public readonly ShortDescription $description,
        public readonly ?Gateway $gateway,
        public readonly ?PaymentAppliedToId $appliedTo,
        public readonly ?Email $senderEmail,
        public readonly ?PersonName $debtorName,
        public readonly ?NationalIdCode $debtorNationalIdCode,
        public readonly ?ProcessorReference $processorReference,
        public readonly ?BankReference $bankReference,
        public readonly ?LegacyPaymentId $legacyPaymentId,
        public readonly ?Iban $iban,
    ) {
        parent::__construct($occuredOn);
    }
}
