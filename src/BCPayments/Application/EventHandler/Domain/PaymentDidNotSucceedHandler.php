<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentDidNotSucceed;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentDidNotSucceedIntegrationEvent;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ExternalEntityId;

class PaymentDidNotSucceedHandler implements EventHandlerInterface
{
    public function __construct(private readonly EventBusInterface $eventBus)
    {
    }

    public function __invoke(PaymentDidNotSucceed $event): void
    {
        $this->eventBus->dispatch(new PaymentDidNotSucceedIntegrationEvent(
            ExternalEntityId::fromString($event->paymentId->toString()),
            $event->appliedTo,
        ));
    }
}
