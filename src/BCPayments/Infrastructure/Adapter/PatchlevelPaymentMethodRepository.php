<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Adapter;

use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentMethodRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodId;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Adapter\PatchlevelRepository;

final class PatchlevelPaymentMethodRepository implements PaymentMethodRepositoryInterface
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
        /** @var PaymentMethod $aggregate */
        $aggregate = $this->repository->load($aggregateId);

        return $aggregate;
    }

    public function has(mixed $aggregateId): bool
    {
        return $this->repository->has($aggregateId);
    }

    public function getIdByDeduplicateKey(string $deduplicateKey): mixed
    {
        /** @var ?PaymentMethodId $aggregateId */
        $aggregateId = $this->repository->getIdByDeduplicateKey($deduplicateKey);

        return $aggregateId;
    }
}
