<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedApplication\Port;

/**
 * @template Aggregate
 * @template AggregateId
 */
interface RepositoryInterface
{
    /**
     * @param Aggregate $aggregate
     * @return void
     */
    public function save(mixed $aggregate, ?string $deduplicateKey = null): void;

    /**
     * @param AggregateId $aggregateId
     * @return Aggregate
     */
    public function load(mixed $aggregateId): mixed;

    /**
     * @param AggregateId $aggregateId
     */
    public function has(mixed $aggregateId): bool;

    /**
     * @return ?AggregateId
     */
    public function getIdByDeduplicateKey(string $deduplicateKey): mixed;
}
