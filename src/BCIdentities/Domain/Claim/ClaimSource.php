<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim;

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

    public function getId(): string
    {
        return $this->id;
    }

    public function getContext(): ClaimContext
    {
        return $this->context;
    }

    public function deterministicKey(): string
    {
        return $this->context->value . '|' . $this->id;
    }

    public static function forPayment(string $paymentId): self
    {
        return new self(ClaimContext::Payment, $paymentId);
    }

    public static function forDonation(string $donationId): self
    {
        return new self(ClaimContext::Donation, $donationId);
    }
}
