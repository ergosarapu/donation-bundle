<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Infrastructure\Adapter;

use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringPlanRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Adapter\PatchlevelRepository;

final class PatchlevelRecurringPlanRepository implements RecurringPlanRepositoryInterface
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
        /** @var RecurringPlan $aggregate */
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
        return RecurringPlanId::fromString($aggregateId->toString());

    }
}
