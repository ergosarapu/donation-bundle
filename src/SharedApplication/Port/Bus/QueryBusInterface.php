<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedApplication\Port\Bus;

interface QueryBusInterface
{
    public function ask(Query $query): mixed;
}
