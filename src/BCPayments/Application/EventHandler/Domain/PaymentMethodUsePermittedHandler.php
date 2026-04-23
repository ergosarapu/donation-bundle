<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\CapturePayment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUsePermitted;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class PaymentMethodUsePermittedHandler implements EventHandlerInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(PaymentMethodUsePermitted $event): void
    {
        $this->commandBus->dispatch(new CapturePayment($event->paymentMethodAction->paymentId, $event->credentialValue));
    }
}
