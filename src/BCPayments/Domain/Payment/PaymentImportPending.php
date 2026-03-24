<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\OrganisationRegCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;

#[Event(name: 'payment.import_pending')]
class PaymentImportPending extends AbstractTimestampedEvent implements DomainEventInterface
{
    public readonly PaymentImportStatus $importStatus;

    public function __construct(
        DateTimeImmutable $occuredOn,
        #[DataSubjectId]
        public readonly PaymentId $paymentId,
        public readonly PaymentImportSourceIdentifier $sourceIdentifier,
        public readonly ?BankReference $bankReference,
        public readonly PaymentStatus $status,
        public readonly Money $amount,
        #[PersonalData]
        public readonly ?ShortDescription $description,
        public readonly DateTimeImmutable $bookingDate,
        #[PersonalData]
        public readonly ?AccountHolderName $accountHolderName,
        #[PersonalData]
        public readonly ?NationalIdCode $nationalIdCode,
        #[PersonalData]
        public readonly ?OrganisationRegCode $organizationRegCode,
        public readonly ?PaymentReference $reference,
        #[PersonalData]
        public readonly ?Iban $iban,
        public readonly ?Bic $bic,
    ) {
        $this->importStatus = PaymentImportStatus::Pending;
        parent::__construct($occuredOn);
    }
}
