<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger;

use Symfony\Contracts\Service\ResetInterface;

final class CorrelationContext implements ResetInterface
{
    public ?string $correlationId = null;

    public function reset(): void
    {
        $this->correlationId = null;
    }
}
