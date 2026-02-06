<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Helpers;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;

class TestEventBus implements EventBusInterface
{
    use TestMessageBusTrait;

    public function __construct(private readonly EventBusInterface $bus)
    {
    }

    public function dispatch(object $message): void
    {
        $this->send($message, true);
    }

    public function send(object $message, bool $intercept = false): void
    {
        $this->dispatched[] = $message;
        if ($intercept) {
            foreach ($this->interceptions as $instanceof) {
                if ($message instanceof $instanceof) {
                    return;
                }
            }
        }
        $this->bus->dispatch($message);
    }
}
