<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ExternalEntityId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class ClaimSource
{
    private function __construct(
        private readonly ClaimContext $context,
        private readonly ExternalEntityId $id,
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

        return PaymentId::fromString($this->id->toString());
    }

    public function getDonationId(): ?DonationId
    {
        if (!$this->isDonationContext()) {
            return null;
        }

        return DonationId::fromString($this->id->toString());
    }

    public function deterministicKey(): string
    {
        return $this->context->value . '|' . $this->id->toString();
    }

    public static function forPayment(PaymentId $paymentId): self
    {
        return new self(ClaimContext::Payment, ExternalEntityId::fromString($paymentId->toString()));
    }

    public static function forDonation(DonationId $donationId): self
    {
        return new self(ClaimContext::Donation, ExternalEntityId::fromString($donationId->toString()));
    }
}
