<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\PresentClaimEvidence;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\ResolveClaim;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\ClaimRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\Claim;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ClaimId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ClaimSource;
use Psr\Clock\ClockInterface;

final class PresentClaimEvidenceHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly ClaimRepositoryInterface $claimRepository,
        private readonly CommandBusInterface $commandBus,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(PresentClaimEvidence $command): void
    {
        $currentTime = $this->clock->now();
        $claimId = ClaimId::generateDeterministic($command->source);
        $claim = $this->loadOrCreateClaim($claimId, $command->source, $currentTime);
        $playhead = $claim->playhead();

        $this->presentClaimEvidence($claim, $command, $currentTime);

        if ($claim->playhead() === $playhead) {
            return;
        }

        $this->claimRepository->save($claim);

        if (!$claim->isResolvable()) {
            return;
        }

        $this->commandBus->dispatch(new ResolveClaim($command->source));
    }

    private function loadOrCreateClaim(ClaimId $claimId, ClaimSource $source, DateTimeImmutable $currentTime): Claim
    {
        if ($this->claimRepository->has($claimId)) {
            return $this->claimRepository->load($claimId);
        }

        return Claim::create($currentTime, $source);
    }

    private function presentClaimEvidence(Claim $claim, PresentClaimEvidence $command, DateTimeImmutable $currentTime): void
    {
        array_map(
            static function (object $presentation) use ($claim, $currentTime): null {
                $claim->present($currentTime, $presentation->value, $presentation->evidenceLevel);
                return null;
            },
            $command->presentations,
        );
    }
}
