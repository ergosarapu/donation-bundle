<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanActionIntent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command\InitiatePaymentIntegrationCommand;
use ErgoSarapu\DonationBundle\IntegrationContracts\ValueObject\EntityId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class DonationInitiatedHandler implements EventHandlerInterface
{
    public function __construct(
        private readonly CommandBusInterface $commandBus
    ) {
    }

    public function __invoke(DonationInitiated $event): void
    {
        $this->commandBus->dispatch(new InitiatePaymentIntegrationCommand(
            $event->amount,
            $event->gateway,
            $event->description,
            new EntityId($event->donationId->toString()),
            $event->donorDetails?->email,
            $event->recurringPlanAction?->intent === RecurringPlanActionIntent::Renew && $event->recurringPlanAction->paymentMethodId !== null
                ? new EntityId($event->recurringPlanAction->paymentMethodId)
                : null,
            $event->recurringPlanAction?->intent === RecurringPlanActionIntent::Init && $event->recurringPlanId !== null
                ? new EntityId($event->recurringPlanId->toString())
                : null,
        ));
    }
}
