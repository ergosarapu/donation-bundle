<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Helpers;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;

class TestCommandBus implements CommandBusInterface
{
    use TestMessageBusTrait;
}
