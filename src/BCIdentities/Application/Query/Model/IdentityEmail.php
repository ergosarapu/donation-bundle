<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model;

class IdentityEmail
{
    use IdTrait;

    private Identity $identity;
    private string $email;

    public function __construct(Identity $identity, string $email)
    {
        $this->identity = $identity;
        $this->email = $email;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getIdentity(): Identity
    {
        return $this->identity;
    }
}
