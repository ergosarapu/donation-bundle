<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedApplication\Port\Bus;

interface EventBusInterface
{
    /**
     * @return string The event tracking ID
     */
    public function dispatch(object $event): string;
}
