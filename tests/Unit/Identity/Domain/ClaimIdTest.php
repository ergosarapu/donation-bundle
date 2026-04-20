<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Identity\Domain;

use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimId;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSource;
use PHPUnit\Framework\TestCase;

class ClaimIdTest extends TestCase
{
    public function testFromString(): void
    {
        $uuid = '018e1234-0000-7000-8000-000000000001';
        $id = ClaimId::fromString($uuid);

        $this->assertInstanceOf(ClaimId::class, $id);
        $this->assertSame($uuid, $id->toString());
    }

    public function testGenerateDeterministicProducesSameIdForSameSource(): void
    {
        $source = ClaimSource::forPayment('018e1234-0000-7000-8000-000000000001');

        $id1 = ClaimId::generate($source);
        $id2 = ClaimId::generate($source);

        $this->assertSame($id1->toString(), $id2->toString());
    }

    public function testGenerateDeterministicProducesExpectedHardcodedValueForPaymentSource(): void
    {
        // This test ensures the deterministic algorithm doesn't change unexpectedly.
        // If this test fails, it means the implementation has changed and existing claim IDs will be invalidated.
        // deterministicKey: "payment|018e1234-0000-7000-8000-000000000001"
        $source = ClaimSource::forPayment('018e1234-0000-7000-8000-000000000001');

        $this->assertSame('a98c28be-a841-589e-a42b-71000b896771', ClaimId::generate($source)->toString());
    }

    public function testGenerateDeterministicProducesExpectedHardcodedValueForDonationSource(): void
    {
        // This test ensures the deterministic algorithm doesn't change unexpectedly.
        // If this test fails, it means the implementation has changed and existing claim IDs will be invalidated.
        // deterministicKey: "donation|018e1234-0000-7000-8000-000000000001"
        $source = ClaimSource::forDonation('018e1234-0000-7000-8000-000000000001');

        $this->assertSame('97a1387d-cf75-5368-b48e-a61a93dc836c', ClaimId::generate($source)->toString());
    }

    public function testGenerateDeterministicProducesDifferentIdsForDifferentPaymentSources(): void
    {
        $source1 = ClaimSource::forPayment('018e1234-0000-7000-8000-000000000001');
        $source2 = ClaimSource::forPayment('018e1234-0000-7000-8000-000000000002');

        $this->assertNotEquals(
            ClaimId::generate($source1)->toString(),
            ClaimId::generate($source2)->toString(),
        );
    }

    public function testGenerateDeterministicProducesDifferentIdsForPaymentAndDonationContextWithSameUuid(): void
    {
        $uuid = '018e1234-0000-7000-8000-000000000001';
        $paymentSource = ClaimSource::forPayment($uuid);
        $donationSource = ClaimSource::forDonation($uuid);

        $this->assertNotEquals(
            ClaimId::generate($paymentSource)->toString(),
            ClaimId::generate($donationSource)->toString(),
        );
    }

    public function testGenerateDeterministicProducesValidUuidV5(): void
    {
        $source = ClaimSource::forPayment('018e1234-0000-7000-8000-000000000001');
        $id = ClaimId::generate($source);
        $parts = explode('-', $id->toString());

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $id->toString());
        $this->assertSame('5', $parts[2][0]);
        $this->assertContains($parts[3][0], ['8', '9', 'a', 'b']);
    }

}
