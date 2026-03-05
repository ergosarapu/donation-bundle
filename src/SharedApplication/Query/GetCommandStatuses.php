<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedApplication\Query;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\Query;

class GetCommandStatuses implements Query
{
    /**
     * @param array<string> $commandCorrelationIds
     */
    public function __construct(
        public readonly array $commandCorrelationIds,
    ) {
    }
}
