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
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;

#[Event(name: 'payment.created')]
class PaymentCreated extends AbstractTimestampedEvent implements DomainEventInterface
{
    public function __construct(
        DateTimeImmutable $occuredOn,
        public readonly DateTimeImmutable $initiatedAt,
        public readonly ?DateTimeImmutable $capturedAt,
        #[DataSubjectId]
        public readonly PaymentId $paymentId,
        public readonly PaymentStatus $status,
        public readonly Money $amount,
        #[PersonalData]
        public readonly ?ShortDescription $description,
        public readonly ?Gateway $gateway,
        public readonly ?PaymentAppliedToId $appliedTo,
        #[PersonalData]
        public readonly ?Email $email,
        #[PersonalData]
        public readonly ?PersonName $name,
        #[PersonalData]
        public readonly ?NationalIdCode $nationalIdCode,
        public readonly ?ProcessorReference $processorReference,
        public readonly ?BankReference $bankReference,
        public readonly ?LegacyPaymentNumber $legacyPaymentNumber,
        #[PersonalData]
        public readonly ?Iban $iban,
    ) {
        parent::__construct($occuredOn);
    }
}
