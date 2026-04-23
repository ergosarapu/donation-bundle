<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\EventHandler\Integration;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\PresentClaimEvidence;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSource;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Event\ClaimPresentedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimerContext;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

final class ClaimPresentedHandler implements EventHandlerInterface
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(ClaimPresentedIntegrationEvent $event): void
    {
        $source = match ($event->claimerContext) {
            ClaimerContext::Donation => ClaimSource::forDonation($event->claimerId->toString()),
            ClaimerContext::Payment => ClaimSource::forPayment($event->claimerId->toString()),
        };

        $this->commandBus->dispatch(new PresentClaimEvidence(
            source: $source,
            presentations: $event->presentations,
        ));
    }
}
