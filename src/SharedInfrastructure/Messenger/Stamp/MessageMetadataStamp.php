<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\Stamp;

use Patchlevel\EventSourcing\Attribute\Header;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Stamp\StampInterface;

#[Header('metadata')]
final readonly class MessageMetadataStamp implements StampInterface
{
    public readonly string $messageId;
    public readonly string $causationId;
    public readonly string $correlationId;

    public function __construct(
        ?string $messageId = null,
        ?string $causationId = null,
        ?string $correlationId = null
    ) {
        $this->messageId = $messageId ?? self::generateId();
        $this->causationId = $causationId ?? self::generateId();
        $this->correlationId = $correlationId ?? self::generateId();
    }

    private static function generateId(): string
    {
        return Uuid::uuid7()->toString();
    }
}
