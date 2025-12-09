<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Integration;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\MarkDonationAsAccepted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentSucceededIntegrationEvent;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class PaymentSucceededHandler implements EventHandlerInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(PaymentSucceededIntegrationEvent $event): void
    {
        if ($event->appliedTo === null) {
            // This event is not for us
            return;
        }

        $this->commandBus->dispatch(new MarkDonationAsAccepted(DonationId::fromString($event->appliedTo->toString()), $event->amount));
    }
}
