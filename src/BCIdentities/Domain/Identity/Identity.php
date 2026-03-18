<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\EntityClaim\EntityClaimId;
use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate(name: 'identity')]
class Identity extends BasicAggregateRoot
{
    #[Id]
    private IdentityId $id;

    /** @var EntityClaimId[] */
    private array $linkedClaimIds = [];

    public static function create(
        DateTimeImmutable $currentTime,
        IdentityId $identityId,
    ): self {
        $identity = new self();
        $identity->recordThat(new IdentityCreated($currentTime, $identityId));
        return $identity;
    }

    #[Apply]
    protected function applyCreated(IdentityCreated $event): void
    {
        $this->id = $event->identityId;
    }

    #[Apply]
    protected function applyClaimLinked(IdentityClaimLinked $event): void
    {
        $this->linkedClaimIds[] = $event->entityClaimId;
    }

    public function linkClaim(DateTimeImmutable $currentTime, EntityClaimId $entityClaimId): void
    {
        $this->recordThat(new IdentityClaimLinked($currentTime, $this->id, $entityClaimId));
    }
}
