<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Patchlevel\EventSourcing\Aggregate\RamseyUuidV7Behaviour;
use Ramsey\Uuid\Uuid;

class PaymentId implements AggregateRootId
{
    use RamseyUuidV7Behaviour;

    /**
     * Generate a deterministic UUIDv7-based PaymentId from a key and a unix timestamp.
     *
     * The caller is responsible for composing a unique key that serves as the seed
     * (e.g. by concatenating source identifier and unique reference), and for
     * stripping any sub-day precision from the timestamp if date-only granularity is desired.
     *
     * This ensures that the same input always generates the same ID.
     *
     * @param string $key The seed key (caller-composed unique string)
     * @param int $timestamp Unix timestamp in milliseconds
     * @return self
     */
    public static function generateDeterministic(
        string $key,
        int $timestamp
    ): self {
        if ($key === '') {
            throw new \InvalidArgumentException('Key must not be empty.');
        }

        if (!mb_check_encoding($key, 'ASCII')) {
            throw new \InvalidArgumentException('Key must contain ASCII characters only.');
        }

        $hash = hash('sha256', $key, true); // Get binary hash (32 bytes)

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
        // 56 more random bits (7 bytes)
        $bytes .= $hash[3] . $hash[4] . $hash[5] . $hash[6] . $hash[7] . $hash[8] . $hash[9];

        // Create UUID from bytes
        $uuid = Uuid::fromBytes($bytes);

        return self::fromString($uuid->toString());
    }
}
