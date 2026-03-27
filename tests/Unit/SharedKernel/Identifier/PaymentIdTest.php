<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\SharedKernel\Identifier;

use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use PHPUnit\Framework\TestCase;

class PaymentIdTest extends TestCase
{
    // 2024-02-01 00:00:00 UTC in milliseconds
    private const TIMESTAMP_MS = 1706745600000;
    // 2024-03-15 00:00:00 UTC in milliseconds
    private const TIMESTAMP_MS_OTHER = 1710460800000;

    public function testGenerate(): void
    {
        $id1 = PaymentId::generate();
        $id2 = PaymentId::generate();

        $this->assertInstanceOf(PaymentId::class, $id1);
        $this->assertInstanceOf(PaymentId::class, $id2);
        $this->assertNotEquals($id1->toString(), $id2->toString());
    }

    public function testFromString(): void
    {
        $uuidString = '01934e6f-8b2a-7890-a123-456789abcdef';
        $id = PaymentId::fromString($uuidString);

        $this->assertInstanceOf(PaymentId::class, $id);
        $this->assertEquals($uuidString, $id->toString());
    }

    public function testGenerateDeterministicProducesSameIdForSameInputs(): void
    {
        $id1 = PaymentId::generateDeterministic('source-123|ref-456', self::TIMESTAMP_MS);
        $id2 = PaymentId::generateDeterministic('source-123|ref-456', self::TIMESTAMP_MS);

        $this->assertEquals($id1->toString(), $id2->toString());
    }

    public function testGenerateDeterministicProducesExpectedHardcodedValue(): void
    {
        // This test ensures the deterministic algorithm doesn't change unexpectedly.
        // If this test fails, it means the implementation has changed and may affect existing data.
        // Timestamp: 2024-02-01 00:00:00 UTC = 1706745600000 ms
        // Key: "test-source-123|test-ref-456"
        $id = PaymentId::generateDeterministic('test-source-123|test-ref-456', self::TIMESTAMP_MS);

        $this->assertEquals('018d61f7-1800-7674-a7f5-15ee46702090', $id->toString());
    }

    public function testGenerateDeterministicProducesDifferentIdForDifferentKey(): void
    {
        $id1 = PaymentId::generateDeterministic('source-123|ref-456', self::TIMESTAMP_MS);
        $id2 = PaymentId::generateDeterministic('source-789|ref-456', self::TIMESTAMP_MS);

        $this->assertNotEquals($id1->toString(), $id2->toString());
    }

    public function testGenerateDeterministicProducesDifferentIdForDifferentTimestamp(): void
    {
        $id1 = PaymentId::generateDeterministic('source-123|ref-456', self::TIMESTAMP_MS);
        $id2 = PaymentId::generateDeterministic('source-123|ref-456', self::TIMESTAMP_MS_OTHER);

        $this->assertNotEquals($id1->toString(), $id2->toString());
    }

    public function testGenerateDeterministicProducesValidUuidV7(): void
    {
        $id = PaymentId::generateDeterministic('source-123|ref-456', self::TIMESTAMP_MS);
        $uuidString = $id->toString();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuidString);

        $parts = explode('-', $uuidString);
        $this->assertEquals('7', $parts[2][0]);
        $this->assertContains($parts[3][0], ['8', '9', 'a', 'b']);
    }

    public function testGenerateDeterministicDifferentTimestampsProduceDifferentTimestampPart(): void
    {
        // 2024-02-02 00:00:00 UTC = 1706832000000 ms
        $id1 = PaymentId::generateDeterministic('source-123|ref-456', self::TIMESTAMP_MS);
        $id2 = PaymentId::generateDeterministic('source-123|ref-456', 1706832000000);

        $parts1 = explode('-', $id1->toString());
        $parts2 = explode('-', $id2->toString());

        $this->assertNotEquals($parts1[0] . $parts1[1], $parts2[0] . $parts2[1]);
    }

    public function testGenerateDeterministicWithEmptyKeyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PaymentId::generateDeterministic('', self::TIMESTAMP_MS);
    }

    public function testGenerateDeterministicWithAsciiSpecialCharacters(): void
    {
        $id = PaymentId::generateDeterministic('source-!@#$%^&*()|ref-456', self::TIMESTAMP_MS);

        $this->assertInstanceOf(PaymentId::class, $id);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $id->toString());
    }

    public function testGenerateDeterministicWithMultibyteKeyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PaymentId::generateDeterministic('ref-émojis-🎉', self::TIMESTAMP_MS);
    }
}
