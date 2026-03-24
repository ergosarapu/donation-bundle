<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model;

class IdentityRawName
{
    private int $id;
    private Identity $identity;
    private string $rawName;

    public function __construct(Identity $identity, string $rawName)
    {
        $this->identity = $identity;
        $this->rawName = $rawName;
    }

    public function getRawName(): string
    {
        return $this->rawName;
    }

    public function getIdentity(): Identity
    {
        return $this->identity;
    }
}
