<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\CommandHandler;

use DateTimeImmutable;
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
            email: $claim->email(),
            iban: $claim->iban(),
            nationalIdCode: $claim->nationalIdCode(),
        );

        if (count($identityIds) > 1) {
            $claim->markInReview($currentTime, ClaimReviewReason::MultipleIdentityMatches);
            $this->claimRepository->save($claim);
            return;
        }

        // Note the lookup may return no matches if the lookup table is eventually consistent and possibly matching identity has not been projected yet
        // In this case we are creating possibly duplicate identity, which may require some cleanup/merge process later.
        $identityId = $identityIds[0] ?? IdentityId::generate();
        $identity = $this->loadOrCreateIdentity($identityId, $currentTime);

        $mergeResult = $identity->mergePersonalData(
            $currentTime,
            $claimId,
            $claim->personName(),
            $claim->nationalIdCode(),
            $claim->rawName(),
            $claim->email(),
            $claim->iban(),
        );

        if ($mergeResult->isConflict()) {
            $claim->markInReview($currentTime, ClaimReviewReason::MergeConflict);
            $this->claimRepository->save($claim);
            return;
        }

        $claim->resolve($currentTime, $identityId);

        $this->transactionManager->transactional(function () use ($claim, $identity): void {
            $this->identityRepository->save($identity);
            $this->claimRepository->save($claim);
        });
    }

    private function loadOrCreateIdentity(IdentityId $identityId, DateTimeImmutable $currentTime): Identity
    {
        if ($this->identityRepository->has($identityId)) {
            return $this->identityRepository->load($identityId);
        }

        return Identity::create($currentTime, $identityId);
    }

}
