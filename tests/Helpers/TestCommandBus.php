<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Helpers;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;

class TestCommandBus implements CommandBusInterface
{
    use TestMessageBusTrait;

    public function __construct(private readonly CommandBusInterface $bus)
    {
    }

    public function dispatch(object $message): mixed
    {
        return $this->send($message, true);
    }

    public function send(object $message, bool $intercept = false): mixed
    {
        $this->dispatched[] = $message;
        if ($intercept) {
            foreach ($this->interceptions as $instanceof) {
                if ($message instanceof $instanceof) {
                    return null;
                }
            }
        }
        return $this->bus->dispatch($message);
    }
}
