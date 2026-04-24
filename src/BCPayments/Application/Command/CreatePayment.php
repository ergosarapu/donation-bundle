<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Command;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\BankReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\GatewayReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\LegacyPaymentNumber;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\LegalIdentifier;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
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
        public readonly ?string $donationId,
        public readonly ?Email $email,
        public readonly ?PersonName $name,
        public readonly ?LegalIdentifier $legalIdentifier,
        public readonly DateTimeImmutable $initiatedAt,
        public readonly ?DateTimeImmutable $capturedAt,
        public readonly ?GatewayReference $gatewayReference,
        public readonly ?BankReference $bankReference,
        public readonly ?PaymentReference $paymentReference,
        public readonly ?LegacyPaymentNumber $legacyPaymentNumber,
        public readonly ?Iban $iban,
    ) {
    }
}
