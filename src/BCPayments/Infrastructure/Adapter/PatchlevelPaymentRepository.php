<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Adapter;

use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Adapter\PatchlevelRepository;

final class PatchlevelPaymentRepository implements PaymentRepositoryInterface
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
        /** @var Payment $aggregate */
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
        return PaymentId::fromString($aggregateId->toString());
    }
}
