<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\ClaimSourceResolutionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSource;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimId;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\ClaimSourceResolution\ClaimSourceResolution;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\ClaimSourceResolution\ClaimSourceResolutionId;
use RuntimeException;

final class ClaimSourceResolver
{
    public function __construct(
        private readonly ClaimSourceResolutionRepositoryInterface $claimSourceResolutionRepository,
    ) {
    }

    public function resolveOrCreate(ClaimSource $source, DateTimeImmutable $currentTime): ClaimId
    {
        $resolution = $this->findResolution($source);
        if ($resolution !== null) {
            return $resolution->claimId();
        }

        $resolution = ClaimSourceResolution::create($currentTime, $source, ClaimId::generate());
        $this->claimSourceResolutionRepository->save($resolution);

        return $resolution->claimId();
    }

    public function resolveOrFail(ClaimSource $source): ClaimId
    {
        $resolution = $this->findResolution($source);
        if ($resolution !== null) {
            return $resolution->claimId();
        }

        throw new RuntimeException(sprintf(
            'Claim source resolution not found for source context "%s" and source id "%s".',
            $source->context->value,
            $source->id,
        ));
    }

    private function findResolution(ClaimSource $source): ?ClaimSourceResolution
    {
        $resolutionId = ClaimSourceResolutionId::fromSource($source);
        if (!$this->claimSourceResolutionRepository->has($resolutionId)) {
            return null;
        }

        return $this->claimSourceResolutionRepository->load($resolutionId);
    }
}
