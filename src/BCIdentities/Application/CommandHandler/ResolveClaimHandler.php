<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\ResolveClaim;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\ClaimRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\IdentityLookupInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\IdentityRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimReviewReason;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\Identity;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\TransactionManagerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ClaimId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\IdentityId;
use Psr\Clock\ClockInterface;

final class ResolveClaimHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly ClaimRepositoryInterface $claimRepository,
        private readonly IdentityLookupInterface $identityLookup,
        private readonly IdentityRepositoryInterface $identityRepository,
        private readonly TransactionManagerInterface $transactionManager,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(ResolveClaim $command): void
    {
        $currentTime = $this->clock->now();
        $claimId = ClaimId::generateDeterministic($command->source);
        $claim = $this->claimRepository->load($claimId);
        $identityIds = $this->identityLookup->lookup(
            email: $claim->email(requireResolutionThreshold: true),
            iban: $claim->iban(requireResolutionThreshold: true),
            nationalIdCode: $claim->nationalIdCode(requireResolutionThreshold: true),
        );

        if (count($identityIds) > 1) {
            $claim->markInReview($currentTime, ClaimReviewReason::MultipleIdentityMatches);
            $this->claimRepository->save($claim);
            return;
        }

        // Note the lookup may return no matches if the lookup table is eventually consistent and possibly matching identity has not been projected yet
        // In this case we are creating possibly duplicate identity, which may require some cleanup/merge process later.
        $identityId = $identityIds[0] ?? null;
        $identity = $identityId === null
            ? Identity::create($currentTime, IdentityId::generate())
            : $this->identityRepository->load($identityId);

        $reviewReason = $identity->resolveClaim($currentTime, $claim);

        if ($reviewReason !== null) {
            $claim->markInReview($currentTime, $reviewReason);
            $this->claimRepository->save($claim);
            return;
        }

        $this->transactionManager->transactional(function () use ($claim, $identity): void {
            $this->identityRepository->save($identity);
            $this->claimRepository->save($claim);
        });
    }
}
