<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedApplication\Port;

interface TransactionManagerInterface
{
    public function transactional(callable $callback): void;
}
