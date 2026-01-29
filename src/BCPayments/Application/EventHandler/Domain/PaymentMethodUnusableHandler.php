<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUnusable;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentMethodUnusableIntegrationEvent;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class PaymentMethodUnusableHandler implements EventHandlerInterface
{
    public function __construct(private readonly EventBusInterface $eventBus)
    {
    }

    public function __invoke(PaymentMethodUnusable $event): void
    {
        $this->eventBus->dispatch(new PaymentMethodUnusableIntegrationEvent(
            $event->paymentMethodId,
        ));
    }
}
