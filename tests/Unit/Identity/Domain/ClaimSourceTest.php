<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Identity\Domain;

use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimContext;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSource;
use PHPUnit\Framework\TestCase;

class ClaimSourceTest extends TestCase
{
    private const UUID = '018f4c8e-1234-7000-8000-000000000001';

    public function testForPaymentCreatesPaymentContext(): void
    {
        $source = ClaimSource::forPayment(self::UUID);

        $this->assertTrue($source->isPaymentContext());
        $this->assertFalse($source->isDonationContext());
    }

    public function testForDonationCreatesDonationContext(): void
    {
        $source = ClaimSource::forDonation(self::UUID);

        $this->assertTrue($source->isDonationContext());
        $this->assertFalse($source->isPaymentContext());
    }

    public function testGetIdReturnsIdForPaymentContext(): void
    {
        $source = ClaimSource::forPayment(self::UUID);

        $this->assertSame(self::UUID, $source->getId());
    }

    public function testGetIdReturnsIdForDonationContext(): void
    {
        $source = ClaimSource::forDonation(self::UUID);

        $this->assertSame(self::UUID, $source->getId());
    }

    public function testGetContextReturnsPaymentForPaymentSource(): void
    {
        $source = ClaimSource::forPayment(self::UUID);

        $this->assertSame(ClaimContext::Payment, $source->getContext());
    }

    public function testGetContextReturnsDonationForDonationSource(): void
    {
        $source = ClaimSource::forDonation(self::UUID);

        $this->assertSame(ClaimContext::Donation, $source->getContext());
    }

    public function testDeterministicKeyForPayment(): void
    {
        // This test ensures the deterministicKey format doesn't change unexpectedly.
        // If this test fails, ClaimId deterministic generation will produce different IDs for existing data.
        $source = ClaimSource::forPayment(self::UUID);

        $this->assertSame('payment|018f4c8e-1234-7000-8000-000000000001', $source->deterministicKey());
    }

    public function testDeterministicKeyForDonation(): void
    {
        // This test ensures the deterministicKey format doesn't change unexpectedly.
        // If this test fails, ClaimId deterministic generation will produce different IDs for existing data.
        $source = ClaimSource::forDonation(self::UUID);

        $this->assertSame('donation|018f4c8e-1234-7000-8000-000000000001', $source->deterministicKey());
    }
}
