<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Adapter\Bus;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\Query;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class SymfonyMessengerQueryBus implements QueryBusInterface
{
    public function __construct(private readonly MessageBusInterface $queryBus)
    {
    }

    public function ask(Query $query): mixed
    {
        return $this->queryBus->dispatch($query)->last(HandledStamp::class)?->getResult();
    }

}
