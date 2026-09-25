<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Infrastructure\Adapter;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\ClaimSourceResolutionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\ClaimSourceResolution\ClaimSourceResolution;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\ClaimSourceResolution\ClaimSourceResolutionId;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Adapter\PatchlevelRepository;

final class PatchlevelClaimSourceResolutionRepository implements ClaimSourceResolutionRepositoryInterface
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
        /** @var ClaimSourceResolution $aggregate */
        $aggregate = $this->repository->load($aggregateId);

        return $aggregate;
    }

    public function has(mixed $aggregateId): bool
    {
        return $this->repository->has($aggregateId);
    }

    public function getIdByDeduplicateKey(string $deduplicateKey): mixed
    {
        $aggregateId = $this->repository->getIdByDeduplicateKey($deduplicateKey);
        if ($aggregateId === null) {
            return null;
        }

        return ClaimSourceResolutionId::fromString($aggregateId->toString());
    }
}
