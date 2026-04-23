<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Integration\Messenger\Helpers;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;

class FirstEventHandler implements CommandHandlerInterface
{
    public function __construct(private readonly EventBusInterface $eventBus)
    {
    }

    public function __invoke(FirstEvent $command): void
    {
        $this->eventBus->dispatch(new SecondEvent());
    }
}
