<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\Stamp;

use Patchlevel\EventSourcing\Attribute\Header;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Stamp\StampInterface;

#[Header('metadata')]
final readonly class MessageMetadataStamp implements StampInterface
{
    public function __construct(
        public readonly string $messageId,
        public readonly string $correlationId,
        public readonly string $trackingId,
        public readonly ?string $causationId = null,
    ) {
    }

    public static function generateId(): string
    {
        return Uuid::uuid7()->toString();
    }
}
