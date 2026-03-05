<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger;

final class MessageContext
{
    private ?string $currentMessageId = null;
    private ?string $currentCorrelationId = null;

    public function getCurrentMessageId(): ?string
    {
        return $this->currentMessageId;
    }

    public function setCurrentMessageId(?string $messageId): void
    {
        $this->currentMessageId = $messageId;
    }

    public function getCurrentCorrelationId(): ?string
    {
        return $this->currentCorrelationId;
    }

    public function setCurrentCorrelationId(?string $correlationId): void
    {
        $this->currentCorrelationId = $correlationId;
    }
}
