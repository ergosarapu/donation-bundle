<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedApplication\Port\Bus;

interface EventBusInterface
{
    public function dispatch(object $event): void;
}
