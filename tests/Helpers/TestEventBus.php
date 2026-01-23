<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Helpers;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;

class TestEventBus implements EventBusInterface
{
    use TestMessageBusTrait;
}
