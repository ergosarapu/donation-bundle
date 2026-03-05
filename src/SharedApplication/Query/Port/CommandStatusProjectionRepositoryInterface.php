<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedApplication\Query\Port;

use ErgoSarapu\DonationBundle\SharedApplication\Query\Model\CommandStatus;

interface CommandStatusProjectionRepositoryInterface
{
    /**
     * @param array<string> $commandCorrelationIds
     * @return array<CommandStatus>
     */
    public function findBy(array $commandCorrelationIds = []): array;
}
