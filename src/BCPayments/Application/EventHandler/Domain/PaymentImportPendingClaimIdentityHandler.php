<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportPending;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Command\ClaimIdentityIntegrationCommand;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;

class PaymentImportPendingClaimIdentityHandler implements EventHandlerInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(PaymentImportPending $event): void
    {
        $iban = $event->iban !== null ? new Iban($event->iban->value) : null;

        $this->commandBus->dispatch(new ClaimIdentityIntegrationCommand(
            claimId: $event->paymentId->toString(),
            source: 'Payments',
            name: $event->accountHolderName?->value,
            email: null,
            iban: $iban,
            nationalIdCode: $event->nationalIdCode,
        ));
    }
}
