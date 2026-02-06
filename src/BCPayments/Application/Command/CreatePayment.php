<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Command;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\BankReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Iban;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\LegacyPaymentId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\ProcessorReference;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;

final class CreatePayment implements CommandInterface
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly PaymentStatus $status,
        public readonly Money $amount,
        public readonly ShortDescription $description,
        public readonly ?Gateway $gateway,
        public readonly ?Email $senderEmail,
        public readonly ?PersonName $senderName,
        public readonly ?NationalIdCode $senderNationalIdCode,
        public readonly ?PaymentAppliedToId $paymentAppliedToId,
        public readonly DateTimeImmutable $initiatedAt,
        public readonly ?DateTimeImmutable $capturedAt,
        public readonly ?ProcessorReference $processorReference,
        public readonly ?BankReference $bankReference,
        public readonly ?LegacyPaymentId $legacyPaymentId,
        public readonly ?Iban $iban,
    ) {
    }
}
