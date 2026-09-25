<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model;

final class ClaimConnection
{
    public const REASON_CORRELATED = 'correlated';
    public const REASON_EMAIL = 'email';
    public const REASON_LEGAL_IDENTIFIER = 'legal_identifier';
    public const REASON_IBAN = 'iban';

    use IdTrait;

    private Claim $claim;
    private string $connectedClaimId;
    private string $reason;

    public function __construct(Claim $claim, string $connectedClaimId, string $reason)
    {
        $this->claim = $claim;
        $this->connectedClaimId = $connectedClaimId;
        $this->reason = $reason;
    }

    public function getClaim(): Claim
    {
        return $this->claim;
    }

    public function getConnectedClaimId(): string
    {
        return $this->connectedClaimId;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
