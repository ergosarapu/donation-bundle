<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedApplication\Port\Bus;

interface CommandBusInterface
{
    public function dispatch(object $command): mixed;
}
