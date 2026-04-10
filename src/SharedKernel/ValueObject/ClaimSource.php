<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class ClaimSource
{
    private function __construct(
        private readonly ClaimContext $context,
        private readonly string $id,
    ) {
    }

    public function isPaymentContext(): bool
    {
        return $this->context === ClaimContext::Payment;
    }

    public function isDonationContext(): bool
    {
        return $this->context === ClaimContext::Donation;
    }

    public function getPaymentId(): ?PaymentId
    {
        if (!$this->isPaymentContext()) {
            return null;
        }

        return PaymentId::fromString($this->id);
    }

    public function getDonationId(): ?DonationId
    {
        if (!$this->isDonationContext()) {
            return null;
        }

        return DonationId::fromString($this->id);
    }

    public function deterministicKey(): string
    {
        return $this->context->value . '|' . $this->id;
    }

    public static function forPayment(PaymentId $paymentId): self
    {
        return new self(ClaimContext::Payment, $paymentId->toString());
    }

    public static function forDonation(DonationId $donationId): self
    {
        return new self(ClaimContext::Donation, $donationId->toString());
    }
}
