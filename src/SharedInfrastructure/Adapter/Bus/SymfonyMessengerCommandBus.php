<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Adapter\Bus;

use ErgoSarapu\DonationBundle\SharedApplication\Message\DelayedMessage;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

class SymfonyMessengerCommandBus implements CommandBusInterface
{
    public function __construct(private readonly MessageBusInterface $commandBus)
    {
    }

    public function dispatch(object $command): mixed
    {
        if ($command instanceof DelayedMessage) {
            return $this->commandBus->dispatch(
                $command->message,
                [DelayStamp::delayUntil($command->delayUntil), new TransportNamesStamp('delayed')]
            )->last(HandledStamp::class)?->getResult();
        }

        return $this->commandBus->dispatch($command)->last(HandledStamp::class)?->getResult();
    }
}
