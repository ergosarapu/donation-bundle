<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\SharedKernel\ValueObject;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ClaimSource;
use PHPUnit\Framework\TestCase;

class ClaimSourceTest extends TestCase
{
    private const UUID = '018f4c8e-1234-7000-8000-000000000001';

    public function testForPaymentCreatesPaymentContext(): void
    {
        $paymentId = PaymentId::fromString(self::UUID);
        $source = ClaimSource::forPayment($paymentId);

        $this->assertTrue($source->isPaymentContext());
        $this->assertFalse($source->isDonationContext());
    }

    public function testForDonationCreatesDonationContext(): void
    {
        $donationId = DonationId::fromString(self::UUID);
        $source = ClaimSource::forDonation($donationId);

        $this->assertTrue($source->isDonationContext());
        $this->assertFalse($source->isPaymentContext());
    }

    public function testGetPaymentIdReturnsIdForPaymentContext(): void
    {
        $paymentId = PaymentId::fromString(self::UUID);
        $source = ClaimSource::forPayment($paymentId);

        $result = $source->getPaymentId();

        $this->assertNotNull($result);
        $this->assertSame(self::UUID, $result->toString());
    }

    public function testGetPaymentIdReturnsNullForDonationContext(): void
    {
        $donationId = DonationId::fromString(self::UUID);
        $source = ClaimSource::forDonation($donationId);

        $this->assertNull($source->getPaymentId());
    }

    public function testGetDonationIdReturnsIdForDonationContext(): void
    {
        $donationId = DonationId::fromString(self::UUID);
        $source = ClaimSource::forDonation($donationId);

        $result = $source->getDonationId();

        $this->assertNotNull($result);
        $this->assertSame(self::UUID, $result->toString());
    }

    public function testGetDonationIdReturnsNullForPaymentContext(): void
    {
        $paymentId = PaymentId::fromString(self::UUID);
        $source = ClaimSource::forPayment($paymentId);

        $this->assertNull($source->getDonationId());
    }

    public function testDeterministicKeyForPayment(): void
    {
        // This test ensures the deterministicKey format doesn't change unexpectedly.
        // If this test fails, ClaimId deterministic generation will produce different IDs for existing data.
        $paymentId = PaymentId::fromString(self::UUID);
        $source = ClaimSource::forPayment($paymentId);

        $this->assertSame('payment|018f4c8e-1234-7000-8000-000000000001', $source->deterministicKey());
    }

    public function testDeterministicKeyForDonation(): void
    {
        // This test ensures the deterministicKey format doesn't change unexpectedly.
        // If this test fails, ClaimId deterministic generation will produce different IDs for existing data.
        $donationId = DonationId::fromString(self::UUID);
        $source = ClaimSource::forDonation($donationId);

        $this->assertSame('donation|018f4c8e-1234-7000-8000-000000000001', $source->deterministicKey());
    }
}
