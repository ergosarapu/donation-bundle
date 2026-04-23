<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedApplication\Port\Bus;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandResult;

interface CommandBusInterface
{
    public function dispatch(object $command): CommandResult;
}
