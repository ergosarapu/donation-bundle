<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\SharedKernel\Identifier;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ClaimId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ClaimSource;
use PHPUnit\Framework\TestCase;

class ClaimIdTest extends TestCase
{
    private const PAYMENT_UUID = '018e1234-0000-7000-8000-000000000001';
    private const DONATION_UUID = '018e1234-0000-7000-8000-000000000001';

    public function testGenerateProducesUniqueIds(): void
    {
        $id1 = ClaimId::generate();
        $id2 = ClaimId::generate();

        $this->assertInstanceOf(ClaimId::class, $id1);
        $this->assertInstanceOf(ClaimId::class, $id2);
        $this->assertNotEquals($id1->toString(), $id2->toString());
    }

    public function testFromString(): void
    {
        $uuid = '018e1234-0000-7000-8000-000000000001';
        $id = ClaimId::fromString($uuid);

        $this->assertInstanceOf(ClaimId::class, $id);
        $this->assertSame($uuid, $id->toString());
    }

    public function testGenerateDeterministicProducesSameIdForSameSource(): void
    {
        $source = ClaimSource::forPayment(PaymentId::fromString(self::PAYMENT_UUID));

        $id1 = ClaimId::generateDeterministic($source);
        $id2 = ClaimId::generateDeterministic($source);

        $this->assertSame($id1->toString(), $id2->toString());
    }

    public function testGenerateDeterministicProducesExpectedHardcodedValueForPaymentSource(): void
    {
        // This test ensures the deterministic algorithm doesn't change unexpectedly.
        // If this test fails, it means the implementation has changed and existing claim IDs will be invalidated.
        // deterministicKey: "payment|018e1234-0000-7000-8000-000000000001"
        $source = ClaimSource::forPayment(PaymentId::fromString(self::PAYMENT_UUID));

        $this->assertSame('5040708b-c7af-7f09-a44f-681b0ca829f8', ClaimId::generateDeterministic($source)->toString());
    }

    public function testGenerateDeterministicProducesExpectedHardcodedValueForDonationSource(): void
    {
        // This test ensures the deterministic algorithm doesn't change unexpectedly.
        // If this test fails, it means the implementation has changed and existing claim IDs will be invalidated.
        // deterministicKey: "donation|018e1234-0000-7000-8000-000000000001"
        $source = ClaimSource::forDonation(DonationId::fromString(self::DONATION_UUID));

        $this->assertSame('ba746ee9-e0bb-788b-8980-f0f89e49ca97', ClaimId::generateDeterministic($source)->toString());
    }

    public function testGenerateDeterministicProducesDifferentIdsForDifferentPaymentSources(): void
    {
        $source1 = ClaimSource::forPayment(PaymentId::fromString('018e1234-0000-7000-8000-000000000001'));
        $source2 = ClaimSource::forPayment(PaymentId::fromString('018e1234-0000-7000-8000-000000000002'));

        $this->assertNotEquals(
            ClaimId::generateDeterministic($source1)->toString(),
            ClaimId::generateDeterministic($source2)->toString(),
        );
    }

    public function testGenerateDeterministicProducesDifferentIdsForPaymentAndDonationContextWithSameUuid(): void
    {
        $uuid = '018e1234-0000-7000-8000-000000000001';
        $paymentSource = ClaimSource::forPayment(PaymentId::fromString($uuid));
        $donationSource = ClaimSource::forDonation(DonationId::fromString($uuid));

        $this->assertNotEquals(
            ClaimId::generateDeterministic($paymentSource)->toString(),
            ClaimId::generateDeterministic($donationSource)->toString(),
        );
    }

    public function testGenerateDeterministicProducesValidUuidV7(): void
    {
        $source = ClaimSource::forPayment(PaymentId::fromString(self::PAYMENT_UUID));
        $id = ClaimId::generateDeterministic($source);
        $parts = explode('-', $id->toString());

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $id->toString());
        $this->assertSame('7', $parts[2][0]);
        $this->assertContains($parts[3][0], ['8', '9', 'a', 'b']);
    }
}
