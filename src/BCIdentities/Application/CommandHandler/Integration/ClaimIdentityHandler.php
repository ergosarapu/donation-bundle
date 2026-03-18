<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\CommandHandler\Integration;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\ClaimIdentity;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\EntityClaim\EntityClaimId;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\EntityClaim\EntityClaimSource;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Command\ClaimIdentityIntegrationCommand;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;

class ClaimIdentityHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(ClaimIdentityIntegrationCommand $command): void
    {
        $this->commandBus->dispatch(new ClaimIdentity(
            EntityClaimId::fromString($command->claimId),
            EntityClaimSource::from($command->source),
            $command->name,
            $command->email,
            $command->iban,
            $command->nationalIdCode,
        ));
    }
}
