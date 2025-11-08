<?php

namespace ErgoSarapu\DonationBundle\SharedApplication\Port\Bus;

interface QueryBusInterface
{
    public function ask(Query $query): mixed;
}
