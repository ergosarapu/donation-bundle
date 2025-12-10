<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationInitiated;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command\InitiatePaymentIntegrationCommand;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;

class DonationInitiatedHandler implements EventHandlerInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(DonationInitiated $event): void
    {
        $useAgreementFrom = null;
        if ($event->recurringToken !== null) {
            // Assuming the recurring token is payment id
            $useAgreementFrom = PaymentId::fromString($event->recurringToken->toString());
        }
        $this->commandBus->dispatch(new InitiatePaymentIntegrationCommand(
            $event->paymentId,
            $event->amount,
            $event->gateway,
            $event->description,
            PaymentAppliedToId::fromString($event->donationId->toString()),
            $event->donorIdentity->email,
            $useAgreementFrom,
        ));
    }
}
