<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\ResolveClaim;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\ClaimRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\IdentityLookupInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\IdentityRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\Claim;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimReviewReason;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\Identity;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\TransactionManagerInterface;
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
        $claimId = $command->claimId;
        $claim = $this->claimRepository->load($claimId);

        // Lookup matching identities
        $matchingIdentityIds = $this->identityLookup->lookup(
            email: $claim->email(),
            iban: $claim->iban(),
            nationalIdCode: $claim->nationalIdCode(),
            organisationRegCode: $claim->organisationRegCode(),
        );

        // More than 1 matching identities, send to review
        if (count($matchingIdentityIds) > 1) {
            $claim->markInReview($currentTime, ClaimReviewReason::MultipleIdentityMatches);
            $this->claimRepository->save($claim);
            return;
        }

        // Since lookup tables may not be fully up to date (eventually consisten),
        // we need to make sure there is no existing identity with the same deduplicate key before creating a new identity.
        // This allows to limit the number of duplicate identities that may be created (e.g. during batch data processing)
        $deduplicateKey = $this->deduplicateKey($claim);

        $identityId = $matchingIdentityIds[0]
            ?? $this->identityRepository->getIdByDeduplicateKey($deduplicateKey)
            ?? IdentityId::generate();

        $identity = $this->loadOrCreateIdentity($identityId, $currentTime);

        $mergeResult = $identity->mergeClaimData(
            $currentTime,
            $claimId,
            $claim->personName(),
            $claim->nationalIdCode(),
            $claim->organisationRegCode(),
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

        $this->transactionManager->transactional(function () use ($claim, $identity, $deduplicateKey): void {
            $this->identityRepository->save($identity, $deduplicateKey);
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

    private function deduplicateKey(
        Claim $claim,
    ): string {
        return json_encode([
            'email' => $claim->email()?->toString(),
            'iban' => $claim->iban()?->value,
            'nationalIdCode' => $claim->nationalIdCode()?->value,
            'organisationRegCode' => $claim->organisationRegCode()?->value,
        ], JSON_THROW_ON_ERROR);
    }

}
