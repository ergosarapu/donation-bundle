<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model;

final class ClaimLinkedClaim
{
    use IdTrait;

    private Claim $claim;
    private string $linkedClaimId;

    public function __construct(Claim $claim, string $linkedClaimId)
    {
        $this->claim = $claim;
        $this->linkedClaimId = $linkedClaimId;
    }

    public function getClaim(): Claim
    {
        return $this->claim;
    }

    public function getLinkedClaimId(): string
    {
        return $this->linkedClaimId;
    }
}
