<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\GenerateRedirectCaptureUrl;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\UsePaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentInitiated;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodActionIntent;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class PaymentInitiatedHandler implements EventHandlerInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(PaymentInitiated $event): void
    {
        if ($event->paymentMethodAction !== null && $event->paymentMethodAction->intent === PaymentMethodActionIntent::Use) {
            $this->commandBus->dispatch(new UsePaymentMethod($event->paymentMethodAction));
            return;
        }

        $requestpaymentMethod = $event->paymentMethodAction?->intent === PaymentMethodActionIntent::Request;
        $this->commandBus->dispatch(new GenerateRedirectCaptureUrl(
            $event->paymentId,
            $event->amount,
            $event->gateway,
            $event->description,
            $event->email,
            $requestpaymentMethod,
        ));
    }
}
