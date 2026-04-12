<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim;

use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSource;
use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Patchlevel\EventSourcing\Aggregate\RamseyUuidV7Behaviour;
use Ramsey\Uuid\Uuid;

final class ClaimId implements AggregateRootId
{
    use RamseyUuidV7Behaviour;

    public static function generateDeterministic(ClaimSource $source): self
    {
        $hash = hash('sha256', $source->deterministicKey(), true);

        $bytes = $hash[0] . $hash[1] . $hash[2] . $hash[3] . $hash[4] . $hash[5];
        $bytes .= chr(0x70 | (ord($hash[6]) & 0x0F));
        $bytes .= $hash[7];
        $bytes .= chr(0x80 | (ord($hash[8]) & 0x3F));
        $bytes .= $hash[9] . $hash[10] . $hash[11] . $hash[12] . $hash[13] . $hash[14] . $hash[15];

        return self::fromString(Uuid::fromBytes($bytes)->toString());
    }
}
