<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityId;
use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate(name: 'claim')]
final class Claim extends BasicAggregateRoot
{
    #[Id]
    private ClaimId $id;
    private ClaimSource $source;
    private ?IdentityId $initialIdentityId = null;
    /** @var array<string, ClaimSource> */
    private array $correlations = [];

    public static function create(
        DateTimeImmutable $currentTime,
        ClaimId $claimId,
        ClaimSource $source,
        ?IdentityId $initialIdentityId = null,
    ): self
    {
        $claim = new self();
        $claim->recordThat(new ClaimCreated($currentTime, $claimId, $source->resolve($claimId), $initialIdentityId));

        return $claim;
    }

    #[Apply]
    protected function applyClaimCreated(ClaimCreated $event): void
    {
        $this->id = $event->claimId;
        $this->source = $event->source;
        $this->initialIdentityId = $event->initialIdentityId;
        $this->correlations = [];
    }

    #[Apply]
    protected function applyClaimCorrelatedWithSource(ClaimCorrelatedWithSource $event): void
    {
        $this->correlations[$this->correlatedSourceKey($event->correlatedSource)] = $event->correlatedSource;
    }

    #[Apply]
    protected function applyClaimCorrelatedSourceRemoved(ClaimCorrelatedSourceRemoved $event): void
    {
        unset($this->correlations[$this->correlatedSourceKey($event->correlatedSource)]);
    }

    private function correlatedSourceKey(ClaimSource $source): string
    {
        return $source->context->value . ':' . $source->id;
    }
}
