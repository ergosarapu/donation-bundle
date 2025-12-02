<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Adapter\Bus;

use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command\IntegrationCommandInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Message\DelayedMessage;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

class SymfonyMessengerCommandBus implements CommandBusInterface
{
    public function __construct(private readonly MessageBusInterface $commandBus)
    {
    }

    public function dispatch(object $command): void
    {
        if ($command instanceof DelayedMessage) {
            $this->commandBus->dispatch(
                $command->message,
                [DelayStamp::delayUntil($command->delayUntil), new TransportNamesStamp('delayed_command')]
            );
            return;
        }

        if ($command instanceof IntegrationCommandInterface) {
            $this->commandBus->dispatch(
                $command,
                [new TransportNamesStamp('integration_command')]
            );
            return;
        }

        $this->commandBus->dispatch($command, [new TransportNamesStamp('command')]);
    }
}
