<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\UsablePaymentMethodCreated;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\UsablePaymentMethodCreatedIntegrationEvent;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class UsablePaymentMethodCreatedHandler implements EventHandlerInterface
{
    public function __construct(private readonly EventBusInterface $eventBus)
    {
    }

    public function __invoke(UsablePaymentMethodCreated $event): void
    {
        $this->eventBus->dispatch(new UsablePaymentMethodCreatedIntegrationEvent(
            $event->paymentMethodId,
            $event->createdFor,
        ));
    }
}
