<?php

namespace ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event\PaymentCaptured;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentCapturedIntegrationEvent;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class PaymentCapturedHandler implements EventHandlerInterface
{

    public function __construct(private readonly EventBusInterface $eventBus)
    {
    }

    public function __invoke(PaymentCaptured $event): void
    {
        $this->eventBus->dispatch(new PaymentCapturedIntegrationEvent(
            $event->paymentId,
            $event->capturedAmount,
            $event->donationId,
        ));
    }
}
