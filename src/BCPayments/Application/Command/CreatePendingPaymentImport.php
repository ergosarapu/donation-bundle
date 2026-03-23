<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Command;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\AccountHolderName;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\BankReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Bic;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportSourceIdentifier;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\OrganisationRegCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;

final class CreatePendingPaymentImport implements CommandInterface
{
    public function __construct(
        public readonly PaymentImportSourceIdentifier $sourceIdentifier,
        public readonly BankReference $bankReference,
        public readonly PaymentStatus $status,
        public readonly Money $amount,
        public readonly ?ShortDescription $description,
        public readonly DateTimeImmutable $bookingDate,
        public readonly ?AccountHolderName $accountHolderName,
        public readonly ?NationalIdCode $nationalIdCode,
        public readonly ?OrganisationRegCode $organizationRegCode,
        public readonly ?PaymentReference $reference,
        public readonly ?Iban $iban,
        public readonly ?Bic $bic,
    ) {
    }
}
