<?php

namespace ErgoSarapu\DonationBundle\SharedApplication\Port\Bus;

interface CommandBusInterface
{
    public function dispatch(object $command): void;
}
