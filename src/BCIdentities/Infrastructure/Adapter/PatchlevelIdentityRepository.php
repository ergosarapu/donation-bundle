<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Infrastructure\Adapter;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\IdentityRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\Identity;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel\PatchlevelRepositoryWrapperTrait;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\IdentityId;

final class PatchlevelIdentityRepository implements IdentityRepositoryInterface
{
    use PatchlevelRepositoryWrapperTrait;

    public function save(Identity $identity): void
    {
        $this->saveAggregate($identity);
    }

    public function load(IdentityId $identityId): Identity
    {
        /** @var Identity $identity */
        $identity = $this->loadAggregate($identityId);
        return $identity;
    }

    public function has(IdentityId $identityId): bool
    {
        return $this->hasAggregate($identityId);
    }
}
