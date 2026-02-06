<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\SharedKernel\Identifier;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use PHPUnit\Framework\TestCase;

class PaymentIdTest extends TestCase
{
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
        $sourceIdentifier = 'source-123';
        $uniqueReference = 'ref-456';
        $effectiveDate = new DateTimeImmutable('2024-02-01 12:00:00');

        $id1 = PaymentId::generateDeterministic($sourceIdentifier, $uniqueReference, $effectiveDate);
        $id2 = PaymentId::generateDeterministic($sourceIdentifier, $uniqueReference, $effectiveDate);

        $this->assertEquals($id1->toString(), $id2->toString());
    }

    public function testGenerateDeterministicProducesExpectedHardcodedValue(): void
    {
        // This test ensures the deterministic algorithm doesn't change unexpectedly.
        // If this test fails, it means the implementation has changed and may affect existing data.
        $sourceIdentifier = 'test-source-123';
        $uniqueReference = 'test-ref-456';
        $effectiveDate = new DateTimeImmutable('2024-02-01 12:00:00', new \DateTimeZone('UTC'));

        $id = PaymentId::generateDeterministic($sourceIdentifier, $uniqueReference, $effectiveDate);

        // Expected UUID generated with the current implementation
        // This is a UUIDv7 based on:
        // - Timestamp: 2024-02-01 12:00:00 UTC (1706788800000 milliseconds)
        // - Hash seed: "test-source-123|test-ref-456"
        $expectedUuid = '018d648a-4600-7674-a7f5-15ee46702090';

        $this->assertEquals($expectedUuid, $id->toString());
    }

    public function testGenerateDeterministicProducesDifferentIdForDifferentSourceIdentifier(): void
    {
        $uniqueReference = 'ref-456';
        $effectiveDate = new DateTimeImmutable('2024-02-01 12:00:00');

        $id1 = PaymentId::generateDeterministic('source-123', $uniqueReference, $effectiveDate);
        $id2 = PaymentId::generateDeterministic('source-789', $uniqueReference, $effectiveDate);

        $this->assertNotEquals($id1->toString(), $id2->toString());
    }

    public function testGenerateDeterministicProducesDifferentIdForDifferentUniqueReference(): void
    {
        $sourceIdentifier = 'source-123';
        $effectiveDate = new DateTimeImmutable('2024-02-01 12:00:00');

        $id1 = PaymentId::generateDeterministic($sourceIdentifier, 'ref-456', $effectiveDate);
        $id2 = PaymentId::generateDeterministic($sourceIdentifier, 'ref-789', $effectiveDate);

        $this->assertNotEquals($id1->toString(), $id2->toString());
    }

    public function testGenerateDeterministicProducesDifferentIdForDifferentEffectiveDate(): void
    {
        $sourceIdentifier = 'source-123';
        $uniqueReference = 'ref-456';

        $id1 = PaymentId::generateDeterministic($sourceIdentifier, $uniqueReference, new DateTimeImmutable('2024-02-01 12:00:00'));
        $id2 = PaymentId::generateDeterministic($sourceIdentifier, $uniqueReference, new DateTimeImmutable('2024-03-15 15:30:00'));

        $this->assertNotEquals($id1->toString(), $id2->toString());
    }

    public function testGenerateDeterministicProducesValidUuidV7(): void
    {
        $sourceIdentifier = 'source-123';
        $uniqueReference = 'ref-456';
        $effectiveDate = new DateTimeImmutable('2024-02-01 12:00:00');

        $id = PaymentId::generateDeterministic($sourceIdentifier, $uniqueReference, $effectiveDate);
        $uuidString = $id->toString();

        // Check UUID format (8-4-4-4-12)
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuidString);

        // Check version is 7 (4th character of 3rd group should be '7')
        $parts = explode('-', $uuidString);
        $this->assertEquals('7', $parts[2][0]);

        // Check variant is RFC 4122 (first character of 4th group should be '8', '9', 'a', or 'b')
        $this->assertContains($parts[3][0], ['8', '9', 'a', 'b']);
    }

    public function testGenerateDeterministicIncludesTimestampFromEffectiveDate(): void
    {
        $sourceIdentifier = 'source-123';
        $uniqueReference = 'ref-456';
        $date1 = new DateTimeImmutable('2024-02-01 12:00:00');
        $date2 = new DateTimeImmutable('2024-02-01 12:00:01'); // 1 second later

        $id1 = PaymentId::generateDeterministic($sourceIdentifier, $uniqueReference, $date1);
        $id2 = PaymentId::generateDeterministic($sourceIdentifier, $uniqueReference, $date2);

        // Different timestamps should produce different IDs even with same source and reference
        $this->assertNotEquals($id1->toString(), $id2->toString());

        // The first part of UUIDv7 contains the timestamp, so check they're different
        $parts1 = explode('-', $id1->toString());
        $parts2 = explode('-', $id2->toString());

        // First two groups contain the timestamp
        $timestamp1 = $parts1[0] . $parts1[1];
        $timestamp2 = $parts2[0] . $parts2[1];

        $this->assertNotEquals($timestamp1, $timestamp2);
    }

    public function testGenerateDeterministicWithEmptyStrings(): void
    {
        $effectiveDate = new DateTimeImmutable('2024-02-01 12:00:00');

        $id = PaymentId::generateDeterministic('', '', $effectiveDate);

        $this->assertInstanceOf(PaymentId::class, $id);
        $this->assertNotEmpty($id->toString());
    }

    public function testGenerateDeterministicWithSpecialCharacters(): void
    {
        $sourceIdentifier = 'source-with-special-chars-!@#$%^&*()';
        $uniqueReference = 'ref-with-unicode-émojis-🎉';
        $effectiveDate = new DateTimeImmutable('2024-02-01 12:00:00');

        $id = PaymentId::generateDeterministic($sourceIdentifier, $uniqueReference, $effectiveDate);

        $this->assertInstanceOf(PaymentId::class, $id);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $id->toString());
    }
}
