<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanActionIntent;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodAction;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentRequest;
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
        $paymentMethodAction = null;
        if ($event->recurringPlanAction !== null) {
            $paymentMethodAction = match ($event->recurringPlanAction->intent) {
                RecurringPlanActionIntent::Init => PaymentMethodAction::forRequest($event->recurringPlanAction->paymentMethodId, $event->paymentId),
                RecurringPlanActionIntent::Renew => PaymentMethodAction::forUse($event->recurringPlanAction->paymentMethodId, $event->paymentId),
            };
        }

        $paymentRequest = new PaymentRequest(
            $event->paymentId,
            $event->amount,
            $event->gateway,
            $event->description,
            PaymentAppliedToId::fromString($event->donationId->toString()),
            $event->donorIdentity->email,
            $paymentMethodAction,
        );

        $this->commandBus->dispatch(new InitiatePaymentIntegrationCommand(
            $paymentRequest,
        ));
    }
}
