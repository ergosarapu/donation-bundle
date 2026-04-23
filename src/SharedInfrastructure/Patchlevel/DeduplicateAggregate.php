<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Aggregate\Uuid as PatchlevelUuid;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate(name: 'deduplicate')]
class DeduplicateAggregate extends BasicAggregateRoot
{
    #[Id]
    private DeduplicateId $id;
    private PatchlevelUuid $entityId;

    public static function create(string $deduplicateKey, AggregateRootId $entityId): self
    {
        $aggregate = new self();
        $aggregate->id = DeduplicateId::generate($deduplicateKey);
        $aggregate->recordThat(
            new DeduplicateCreated(
                $aggregate->id,
                PatchlevelUuid::fromString($entityId->toString()),
                $deduplicateKey
            )
        );
        return $aggregate;
    }

    #[Apply]
    protected function applyCreated(DeduplicateCreated $event): void
    {
        $this->id = $event->deduplicateId;
        $this->entityId = $event->entityId;
    }

    public function getEntityId(): PatchlevelUuid
    {
        return $this->entityId;
    }
}
