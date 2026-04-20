<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Integration\Messenger\Helpers;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\RepositoryInterface;
use Patchlevel\EventSourcing\Aggregate\Uuid;

class FirstCommandHandler implements CommandHandlerInterface
{
    /**
     * @param RepositoryInterface<TestAggregate, Uuid> $repository
     */
    public function __construct(
        private readonly RepositoryInterface $repository,
    ) {
    }

    public function __invoke(FirstCommand $command): void
    {
        $this->repository->save(TestAggregate::create());
    }
}
