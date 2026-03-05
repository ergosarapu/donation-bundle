<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Adapter\Bus;

use ErgoSarapu\DonationBundle\SharedApplication\Message\DelayedMessage;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandResult;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\Stamp\MessageMetadataStamp;
use RuntimeException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

class SymfonyMessengerCommandBus implements CommandBusInterface
{
    public function __construct(private readonly MessageBusInterface $commandBus)
    {
    }

    public function dispatch(object $command): CommandResult
    {
        if ($command instanceof DelayedMessage) {
            $envelope = $this->commandBus->dispatch(
                $command->message,
                [DelayStamp::delayUntil($command->delayUntil), new TransportNamesStamp('delayed')]
            );
        } else {
            $envelope = $this->commandBus->dispatch($command);
        }

        $result =  $envelope->last(HandledStamp::class)?->getResult();
        $metadata = $envelope->last(MessageMetadataStamp::class);
        if ($metadata === null) {
            throw new RuntimeException('MessageMetadataStamp is missing from the envelope.');
        }

        return new CommandResult(
            result: $result,
            correlationId: $metadata->correlationId
        );
    }
}
