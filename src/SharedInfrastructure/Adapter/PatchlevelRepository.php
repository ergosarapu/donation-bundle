<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Adapter;

use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\RepositoryException as AppRepositoryException;
use ErgoSarapu\DonationBundle\SharedApplication\Port\RepositoryInterface;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel\DeduplicateAggregate;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel\DeduplicateId;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Patchlevel\EventSourcing\Aggregate\Uuid;
use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\Repository;
use Patchlevel\EventSourcing\Repository\RepositoryException;
use Patchlevel\EventSourcing\Store\Store;
use RuntimeException;

/**
 * @implements RepositoryInterface<AggregateRoot, AggregateRootId>
 */
class PatchlevelRepository implements RepositoryInterface
{
    /**
     * @param Repository<AggregateRoot> $aggregateRepository
     * @param Repository<DeduplicateAggregate> $deduplicateRepository
     */
    public function __construct(
        private readonly Repository $aggregateRepository,
        private readonly ?Store $store = null,
        private readonly ?Repository $deduplicateRepository = null,
    ) {
    }

    public function save(mixed $aggregate, ?string $deduplicateKey = null): void
    {
        try {
            if ($deduplicateKey !== null) {
                if ($this->deduplicateRepository === null) {
                    throw new RuntimeException('Deduplicate repository not provided');
                }
                if ($this->store === null) {
                    throw new RuntimeException('Store not provided');
                }

                $this->store->transactional(
                    function () use ($aggregate, $deduplicateKey) {
                        $this->aggregateRepository->save($aggregate);
                        if ($this->hasDeduplicateAggregate($deduplicateKey)) {
                            return;
                        }
                        $deduplicate = DeduplicateAggregate::create($deduplicateKey, $aggregate->aggregateRootId());
                        $this->deduplicateRepository->save($deduplicate);
                    }
                );
                return;
            }
            $this->aggregateRepository->save($aggregate);
        } catch (RepositoryException $e) {
            throw $this->toApplicationException($e);
        }
    }

    public function load(mixed $aggregateId): mixed
    {
        try {
            return $this->aggregateRepository->load($aggregateId);
        } catch (RepositoryException $e) {
            throw $this->toApplicationException($e);
        }
    }

    public function has(mixed $aggregateId): bool
    {
        return $this->aggregateRepository->has($aggregateId);
    }

    public function getIdByDeduplicateKey(string $deduplicateKey): mixed
    {
        $id = $this->getEntityIdByDeduplicateKey($deduplicateKey);
        if ($id === null) {
            return null;
        }
        return $id;
    }

    private function hasDeduplicateAggregate(string $deduplicateKey): bool
    {
        if ($this->deduplicateRepository === null) {
            throw new RuntimeException('Deduplicate repository not provided');
        }
        try {
            return $this->deduplicateRepository->has(DeduplicateId::generate($deduplicateKey));
        } catch (RepositoryException $e) {
            throw $this->toApplicationException($e);
        }
    }

    private function getEntityIdByDeduplicateKey(string $deduplicateKey): ?Uuid
    {
        if (!$this->hasDeduplicateAggregate($deduplicateKey)) {
            return null;
        }
        $deduplicate = $this->loadDeduplicateAggregate($deduplicateKey);
        if ($deduplicate instanceof DeduplicateAggregate === false) {
            throw new RuntimeException('Deduplicate aggregate is not instance of DeduplicateAggregate');
        }
        if (!$this->has($deduplicate->getEntityId())) {
            return null;
        }
        return $deduplicate->getEntityId();
    }

    private function loadDeduplicateAggregate(string $deduplicateKey): mixed
    {
        if ($this->deduplicateRepository === null) {
            throw new RuntimeException('Deduplicate repository not provided');
        }
        try {
            return $this->deduplicateRepository->load(DeduplicateId::generate($deduplicateKey));
        } catch (RepositoryException $e) {
            throw $this->toApplicationException($e);
        }
    }

    private function toApplicationException(RepositoryException $e): AppRepositoryException
    {
        return match (true) {
            $e instanceof AggregateAlreadyExists => new AggregateAlreadyExistsException(previous: $e),
            default => new AppRepositoryException('Repository exception', 0, $e),
        };
    }
}
