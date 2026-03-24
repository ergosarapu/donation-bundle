<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Adapter;

use ErgoSarapu\DonationBundle\SharedApplication\Port\TransactionManagerInterface;
use Patchlevel\EventSourcing\Store\Store;

final class PatchlevelTransactionManager implements TransactionManagerInterface
{
    public function __construct(
        private readonly Store $store,
    ) {
    }

    public function transactional(callable $callback): void
    {
        $this->store->transactional($callback(...));
    }
}
