<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentSucceeded;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentSucceededIntegrationEvent;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ExternalEntityId;

class PaymentSucceededHandler implements EventHandlerInterface
{
    public function __construct(private readonly EventBusInterface $eventBus)
    {
    }

    public function __invoke(PaymentSucceeded $event): void
    {
        $this->eventBus->dispatch(new PaymentSucceededIntegrationEvent(
            ExternalEntityId::fromString($event->paymentId->toString()),
            $event->amount,
            $event->appliedTo,
        ));
    }
}
