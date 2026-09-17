<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model;

class ClaimCorrelatedSource
{
    use IdTrait;

    private Claim $claim;
    private string $correlatedSourceId;

    public function __construct(Claim $claim, string $correlatedSourceId)
    {
        $this->claim = $claim;
        $this->correlatedSourceId = $correlatedSourceId;
    }

    public function getClaim(): Claim
    {
        return $this->claim;
    }

    public function getCorrelatedSourceId(): string
    {
        return $this->correlatedSourceId;
    }
}
