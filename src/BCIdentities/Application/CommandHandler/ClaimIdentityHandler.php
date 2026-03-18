<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\ClaimIdentity;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\EntityClaimRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\IdentityRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\EntityClaim\EntityClaim;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\Identity;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class ClaimIdentityHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly EntityClaimRepositoryInterface $entityClaimRepository,
        private readonly IdentityRepositoryInterface $identityRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(ClaimIdentity $command): void
    {
        $currentTime = DateTimeImmutable::createFromInterface($this->clock->now());

        $entityClaimId = $command->entityClaimId;

        if ($this->entityClaimRepository->has($entityClaimId)) {
            // Claim already exists — idempotent, skip
            return;
        }

        $entityClaim = EntityClaim::create(
            $currentTime,
            $entityClaimId,
            $command->source,
            $command->name,
            $command->email,
            $command->iban,
            $command->nationalIdCode,
        );

        $identityId = IdentityId::generate();
        $identity = Identity::create($currentTime, $identityId);

        $identity->linkClaim($currentTime, $entityClaimId);
        $entityClaim->linkToIdentity($currentTime, $identityId);

        $this->identityRepository->save($identity);
        $this->entityClaimRepository->save($entityClaim);
    }
}
