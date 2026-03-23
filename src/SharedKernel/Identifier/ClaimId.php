<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\Identifier;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ClaimSource;
use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Patchlevel\EventSourcing\Aggregate\RamseyUuidV7Behaviour;
use Ramsey\Uuid\Uuid;

final class ClaimId implements AggregateRootId
{
    use RamseyUuidV7Behaviour;

    public static function generateDeterministic(ClaimSource $source): self
    {
        $hash = hash('sha256', $source->deterministicKey(), true);

        $bytes = substr($hash, 0, 6);
        $bytes .= chr(0x70 | (ord($hash[6]) & 0x0F));
        $bytes .= $hash[7];
        $bytes .= chr(0x80 | (ord($hash[8]) & 0x3F));
        $bytes .= substr($hash, 9, 7);

        return self::fromString(Uuid::fromBytes($bytes)->toString());
    }
}
