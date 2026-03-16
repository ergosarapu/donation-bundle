<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanActionIntent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command\InitiatePaymentIntegrationCommand;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;

class DonationInitiatedHandler implements EventHandlerInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(DonationInitiated $event): void
    {
        $this->commandBus->dispatch(new InitiatePaymentIntegrationCommand(
            $event->paymentId,
            $event->amount,
            $event->gateway,
            $event->description,
            PaymentAppliedToId::fromString($event->donationId->toString()),
            $event->donorDetails?->email,
            $event->recurringPlanAction?->paymentMethodId,
            $event->recurringPlanAction?->intent === RecurringPlanActionIntent::Renew ? true : false,
        ));
    }
}
