<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\Identifier;

use DateTimeImmutable;
use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Patchlevel\EventSourcing\Aggregate\RamseyUuidV7Behaviour;
use Ramsey\Uuid\Uuid;

class PaymentId implements AggregateRootId
{
    use RamseyUuidV7Behaviour;

    /**
     * Generate a deterministic UUIDv7-based PaymentId from source identifier,
     * unique reference, and date.
     *
     * This ensures that the same payment data always generates the same ID,
     * enabling idempotent imports.
     *
     * @param string $sourceIdentifier The source system identifier
     * @param string $uniqueReference The unique reference (e.g. processor reference or bank reference)
     * @param DateTimeImmutable $date The date of the payment
     * @return self
     */
    public static function generateDeterministic(
        string $sourceIdentifier,
        string $uniqueReference,
        DateTimeImmutable $date
    ): self {
        // Create a deterministic seed from source id and unique reference
        $seed = $sourceIdentifier . '|' . $uniqueReference;
        $hash = hash('sha256', $seed, true); // Get binary hash (32 bytes)

        // UUIDv7 structure: 48-bit timestamp + 12-bit random + 62-bit random
        // Get milliseconds timestamp for UUIDv7
        $timestamp = (int) ($date->getTimestamp() * 1000);

        // Build UUIDv7 bytes (16 bytes total)
        $bytes = '';

        // Bytes 0-5: 48-bit timestamp (6 bytes)
        $bytes .= pack('n', ($timestamp >> 32) & 0xFFFF); // Upper 16 bits
        $bytes .= pack('N', $timestamp & 0xFFFFFFFF);     // Lower 32 bits

        // Bytes 6-7: version (4 bits = 0x7) + 12-bit random from hash
        $bytes .= chr(0x70 | (ord($hash[0]) & 0x0F)); // Version 7 + 4 random bits
        $bytes .= $hash[1]; // 8 more random bits

        // Bytes 8-15: variant (2 bits = 0b10) + 62-bit random from hash
        $bytes .= chr(0x80 | (ord($hash[2]) & 0x3F)); // Variant 10 + 6 random bits
        $bytes .= substr($hash, 3, 7); // 56 more random bits (7 bytes)

        // Create UUID from bytes
        $uuid = Uuid::fromBytes($bytes);

        return self::fromString($uuid->toString());
    }
}
