<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\GetIdentity;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model\Identity;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Port\IdentityProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;

final class GetIdentityHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly IdentityProjectionRepositoryInterface $identityRepository,
    ) {
    }

    public function __invoke(GetIdentity $query): ?Identity
    {
        return $this->identityRepository->find($query->identityId);
    }
}
