<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Helpers;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandResult;
use Ramsey\Uuid\Uuid;

class TestCommandBus implements CommandBusInterface
{
    use TestMessageBusTrait;

    public function __construct(private readonly CommandBusInterface $bus)
    {
    }

    public function dispatch(object $message): CommandResult
    {
        return $this->send($message, true);
    }

    public function send(object $message, bool $intercept = false): CommandResult
    {
        $this->dispatched[] = $message;
        if ($intercept) {
            foreach ($this->interceptions as $instanceof) {
                if ($message instanceof $instanceof) {
                    return new CommandResult(null, Uuid::uuid4()->toString());
                }
            }
        }
        return $this->bus->dispatch($message);
    }
}
