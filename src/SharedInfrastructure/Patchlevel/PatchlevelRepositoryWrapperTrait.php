<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel;

use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\RepositoryException as AppRepositoryException;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\Repository;
use Patchlevel\EventSourcing\Repository\RepositoryException;

trait PatchlevelRepositoryWrapperTrait
{
    /**
    * @param Repository<AggregateRoot> $repository
    */
    public function __construct(
        private readonly Repository $repository
    ) {
    }

    private function saveAggregate(AggregateRoot $aggregate): void
    {
        try {
            $this->repository->save($aggregate);
        } catch (RepositoryException $e) {
            throw $this->toApplicationException($e);
        }
    }

    private function loadAggregate(AggregateRootId $id): mixed
    {
        try {
            return $this->repository->load($id);
        } catch (RepositoryException $e) {
            throw $this->toApplicationException($e);
        }
    }

    private function hasAggregate(AggregateRootId $id): bool
    {
        return $this->repository->has($id);
    }

    private function toApplicationException(RepositoryException $e): AppRepositoryException
    {
        return match (true) {
            $e instanceof AggregateAlreadyExists => new AggregateAlreadyExistsException(previous: $e),
            default => new AppRepositoryException('Repository exception', 0, $e),
        };
    }
}
