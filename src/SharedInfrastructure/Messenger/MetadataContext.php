<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger;

use ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\Stamp\MessageMetadataStamp;

final class MetadataContext
{
    private ?string $correlationId = null;
    private ?string $previousMessageId = null;
    private ?string $trackingId = null;

    public function set(?string $correlationId, ?string $previousMessageId, ?string $trackingId): void
    {
        $this->correlationId = $correlationId;
        $this->previousMessageId = $previousMessageId;
        $this->trackingId = $trackingId;
    }

    public function isInitialized(): bool
    {
        return $this->correlationId !== null && $this->previousMessageId !== null;
    }

    public function getCorrelationId(): ?string
    {
        return $this->correlationId;
    }

    public function getPreviousMessageId(): ?string
    {
        return $this->previousMessageId;
    }

    public function getTrackingId(): ?string
    {
        return $this->trackingId;
    }

    public function createStamp(): MessageMetadataStamp
    {
        return new MessageMetadataStamp(
            messageId: MessageMetadataStamp::generateId(),
            correlationId: $this->correlationId ?? MessageMetadataStamp::generateId(),
            trackingId: $this->trackingId ?? MessageMetadataStamp::generateId(),
            causationId: $this->previousMessageId,
        );
    }
}
