<?php

namespace ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event\PaymentDidNotSucceed;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentDidNotSucceedIntegrationEvent;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class PaymentDidNotSucceedHandler implements EventHandlerInterface
{

    public function __construct(private readonly EventBusInterface $eventBus)
    {
    }

    public function __invoke(PaymentDidNotSucceed $event): void
    {
        $this->eventBus->dispatch(new PaymentDidNotSucceedIntegrationEvent(
            $event->paymentId,
            $event->appliedTo,
        ));
    }
}
