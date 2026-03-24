<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\PresentClaimEvidence;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\ResolveClaim;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\ClaimRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\Claim;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ClaimId;
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
        $claim = $this->claimRepository->has($claimId)
            ? $this->claimRepository->load($claimId)
            : Claim::create($currentTime, $command->source);

        foreach ($command->presentations as $presentation) {
            $claim->present($currentTime, $presentation->value, $presentation->evidenceLevel);
        }

        $this->claimRepository->save($claim);

        if ($claim->isResolvable()) {
            $this->commandBus->dispatch(new ResolveClaim($command->source));
        }
    }
}
