<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\ClaimSourceResolution;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimId;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSource;
use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate(name: 'source_resolution')]
final class ClaimSourceResolution extends BasicAggregateRoot
{
    #[Id]
    private ClaimSourceResolutionId $id;
    private ClaimId $claimId;

    public static function create(DateTimeImmutable $currentTime, ClaimSource $source, ClaimId $claimId): self
    {
        $resolver = new self();
        $resolver->recordThat(new ClaimSourceResolutionCreated(
            $currentTime,
            ClaimSourceResolutionId::fromSource($source),
            $claimId,
        ));

        return $resolver;
    }

    #[Apply]
    protected function applyClaimSourceResolutionCreated(ClaimSourceResolutionCreated $event): void
    {
        $this->id = $event->claimSourceResolutionId;
        $this->claimId = $event->claimId;
    }

    public function claimSourceResolutionId(): ClaimSourceResolutionId
    {
        return $this->id;
    }

    public function claimId(): ClaimId
    {
        return $this->claimId;
    }
}
