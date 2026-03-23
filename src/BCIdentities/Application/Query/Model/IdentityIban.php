<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model;

final class IdentityIban
{
    private int $id;
    private Identity $identity;
    private string $iban;

    public function __construct(Identity $identity, string $iban)
    {
        $this->identity = $identity;
        $this->iban = $iban;
    }

    public function getIban(): string
    {
        return $this->iban;
    }

    public function getIdentity(): Identity
    {
        return $this->identity;
    }
}
