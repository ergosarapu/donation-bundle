<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Application\ClaimSourceResolver;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\PresentClaimEvidence;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\ClaimRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\Claim;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimId;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSource;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityId;
use Psr\Clock\ClockInterface;

final class ClaimSourceCommandHandler
{
    public function __construct(
        private readonly ClaimRepositoryInterface $claimRepository,
        private readonly ClaimSourceResolver $claimSourceResolver,
        private readonly ClockInterface $clock,
    ) {
    }

    public function presentClaimEvidence(PresentClaimEvidence $command): void
    {
        $currentTime = $this->clock->now();
        $claimId = $this->claimSourceResolver->resolveOrCreate($command->claimSource, $currentTime);
        $claim = $this->loadOrCreateClaim($claimId, $command->claimSource, $currentTime);
        foreach ($command->correlatedSources as $correlatedSource) {
            $correlatedClaimId = $this->claimSourceResolver->resolveOrCreate($correlatedSource, $currentTime);
            if ($correlatedClaimId->toString() === $claimId->toString()) {
                continue;
            }

            $this->createClaimIfMissing($correlatedClaimId, $correlatedSource, $currentTime);
            $claim->correlate(
                $currentTime,
                $correlatedSource,
                $correlatedClaimId,
            );
        }
        foreach ($command->presentations as [$value, $evidenceLevel]) {
            $claim->present($currentTime, $value, $evidenceLevel);
        }
        $this->claimRepository->save($claim);
    }

    private function loadOrCreateClaim(
        ClaimId $claimId,
        ClaimSource $claimSource,
        DateTimeImmutable $currentTime
    ): Claim {
        if ($this->claimRepository->has($claimId)) {
            $claim = $this->claimRepository->load($claimId);
            return $claim;
        }

        return Claim::create(
            $currentTime,
            $claimId,
            $claimSource,
            IdentityId::fromString($claimId->toString()),
        );
    }

    private function createClaimIfMissing(
        ClaimId $claimId,
        ClaimSource $claimSource,
        DateTimeImmutable $currentTime,
    ): void {
        if ($this->claimRepository->has($claimId)) {
            return;
        }

        $this->claimRepository->save(Claim::create(
            $currentTime,
            $claimId,
            $claimSource,
            IdentityId::fromString($claimId->toString()),
        ));
    }
}
