<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Infrastructure\Adapter;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\IdentityRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\Identity;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityId;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Adapter\PatchlevelRepository;

final class PatchlevelIdentityRepository implements IdentityRepositoryInterface
{
    public function __construct(
        private readonly PatchlevelRepository $repository,
    ) {
    }

    public function save(mixed $aggregate, ?string $deduplicateKey = null): void
    {
        $this->repository->save($aggregate, $deduplicateKey);
    }

    public function load(mixed $aggregateId): mixed
    {
        /** @var Identity $aggregate */
        $aggregate = $this->repository->load($aggregateId);

        return $aggregate;
    }

    public function has(mixed $aggregateId): bool
    {
        return $this->repository->has($aggregateId);
    }

    public function getIdByDeduplicateKey(string $deduplicateKey): mixed
    {
        /** @var ?IdentityId $aggregateId */
        $aggregateId = $this->repository->getIdByDeduplicateKey($deduplicateKey);

        return $aggregateId;
    }
}
