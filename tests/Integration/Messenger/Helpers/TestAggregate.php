<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Integration\Messenger\Helpers;

use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Aggregate\Uuid;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate('test')]
class TestAggregate extends BasicAggregateRoot
{
    #[Id]
    private Uuid $id;

    public static function create(): self
    {
        $self = new self();
        $self->id = Uuid::generate();
        $self->recordThat(new FirstEvent($self->id->toString()));
        return $self;
    }

    #[Apply]
    public function applyTestCreated(FirstEvent $event): void
    {
        $this->id = Uuid::fromString($event->id);
    }
}
